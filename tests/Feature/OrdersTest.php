<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private function service(string $unit = 'm2', int $minimum = 1): Service
    {
        return Service::create(['code' => 'S-'.uniqid(), 'name' => 'Layanan uji', 'unit' => $unit, 'base_price' => 25000, 'is_active' => true, 'minimum_quantity' => $minimum]);
    }

    private function payload(Service $service, array $extra = []): array
    {
        return array_replace(['service_id' => $service->id, 'quantity' => 3, 'width' => '2.5', 'height' => '1.2', 'design_mode' => 'assistance', 'customer_notes' => 'Buat desain simulasi warna biru', 'delivery_method' => 'pickup'], $extra);
    }

    private function order(string $unit = 'm2', array $extra = []): Order
    {
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $this->post('/pelanggan/orders', $this->payload($this->service($unit), $extra))->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    public function test_decimal_banner_volume_is_computed_on_server_and_price_input_is_ignored(): void
    {
        $order = $this->order('m2', ['volume' => 999, 'estimated_total' => 1, 'final_total' => 1, 'status' => 'completed']);
        $this->assertSame('9.0000', $order->volume);
        $this->assertSame('225000.00', $order->estimated_total);
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->final_total);
        $this->get('/pelanggan/orders')->assertOk()->assertSee($order->number);
        $this->get('/pelanggan/orders/'.$order->id)->assertOk()->assertSee('9 m²');
    }

    public function test_large_brochure_and_card_quantities_and_single_piece_are_allowed(): void
    {
        foreach (['lembar', 'pcs'] as $unit) {
            $order = $this->order($unit, ['quantity' => 250]);
            $this->assertSame('250.0000', $order->volume);
            $this->assertNull($order->width);
            $this->assertNull($order->height);
        }
        $order = $this->order('pcs', ['quantity' => 1]);
        $this->assertSame(1, $order->quantity);
    }

    public function test_meter_service_uses_length_without_height(): void
    {
        $order = $this->order('m', ['quantity' => 2, 'width' => '3.25', 'height' => 999]);
        $this->assertSame('6.5000', $order->volume);
        $this->assertNull($order->height);
    }

    public function test_inactive_service_minimum_quantity_and_dimensions_are_enforced(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $service = $this->service('m2', 100);
        $this->post('/pelanggan/orders', $this->payload($service, ['quantity' => 1]))->assertSessionHasErrors('quantity');
        $this->post('/pelanggan/orders', $this->payload($service, ['quantity' => 100, 'height' => 0]))->assertSessionHasErrors('height');
        $service->update(['is_active' => false]);
        $this->post('/pelanggan/orders', $this->payload($service))->assertSessionHasErrors('service_id');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shipping_address_design_and_assistance_notes_are_required(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $service = $this->service();
        $this->post('/pelanggan/orders', $this->payload($service, ['delivery_method' => 'shipping', 'customer_notes' => null]))->assertSessionHasErrors(['customer_notes', 'delivery_address']);
        $this->post('/pelanggan/orders', $this->payload($service, ['design_mode' => 'upload']))->assertSessionHasErrors('design');
        $this->post('/pelanggan/orders', $this->payload($service, ['design' => UploadedFile::fake()->create('unsafe.html', 1, 'text/html')]))->assertSessionHasErrors('design');
    }

    public function test_design_is_private_and_other_customer_cannot_access_any_order_action(): void
    {
        Storage::fake('local');
        $order = $this->order('pcs', ['design_mode' => 'upload', 'design' => UploadedFile::fake()->image('desain.jpg')]);
        Storage::disk('local')->assertExists($order->design_path);
        $this->get('/pelanggan/orders/'.$order->id.'/design')->assertOk()->assertDownload();
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $this->get('/pelanggan/orders')->assertDontSee($order->number);
        $this->get('/pelanggan/orders/'.$order->id)->assertNotFound();
        $this->get('/pelanggan/orders/'.$order->id.'/design')->assertNotFound();
        foreach (['approve', 'cancel', 'revise'] as $action) {
            $this->post('/pelanggan/orders/'.$order->id.'/'.$action)->assertNotFound();
        }
        $this->post('/admin/orders/'.$order->id.'/review', [])->assertForbidden();
    }

    public function test_price_approval_and_progress_follow_workflow_and_delivery_method(): void
    {
        $order = $this->order('m2', ['delivery_method' => 'shipping', 'delivery_address' => 'Alamat simulasi']);
        $customer = auth()->user();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get('/admin/orders')->assertOk()->assertSee($order->number);
        $this->get('/admin/orders/'.$order->id)->assertOk();
        $this->post('/admin/orders/'.$order->id.'/progress', ['status' => 'processing'])->assertSessionHasErrors('status');
        $this->post('/admin/orders/'.$order->id.'/review', ['action' => 'quote', 'final_total' => '300000.50', 'admin_notes' => 'Termasuk bantuan desain dan kirim'])->assertRedirect();
        $this->actingAs($customer);
        $this->post('/pelanggan/orders/'.$order->id.'/approve', ['accepted_total' => $order->fresh()->final_total])->assertRedirect();
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->post('/pelanggan/orders/'.$order->id.'/cancel')->assertSessionHasErrors('action');
        $this->actingAs($admin);
        foreach (['processing', 'completed'] as $status) {
            $this->post('/admin/orders/'.$order->id.'/progress', ['status' => $status])->assertRedirect();
        }
        $this->post('/admin/orders/'.$order->id.'/progress', ['status' => 'collected'])->assertSessionHasErrors('status');
        $this->post('/admin/orders/'.$order->id.'/progress', ['status' => 'shipped', 'note' => 'Resi dummy'])->assertRedirect();
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame(6, $order->events()->count());
    }

    public function test_pickup_completion_and_duplicate_action_do_not_duplicate_history(): void
    {
        $order = $this->order('pcs');
        $customer = auth()->user();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/orders/'.$order->id.'/review', ['action' => 'quote', 'final_total' => 1000])->assertRedirect();
        $this->actingAs($customer);
        $this->post('/pelanggan/orders/'.$order->id.'/approve', ['accepted_total' => $order->fresh()->final_total])->assertRedirect();
        $this->post('/pelanggan/orders/'.$order->id.'/approve', ['accepted_total' => $order->fresh()->final_total])->assertSessionHasErrors('action');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['processing', 'completed', 'collected'] as $status) {
            $this->post('/admin/orders/'.$order->id.'/progress', ['status' => $status])->assertRedirect();
        }
        $this->assertSame('collected', $order->fresh()->status);
        $this->assertSame(6, $order->events()->count());
    }

    public function test_revision_can_replace_design_and_go_back_to_pending(): void
    {
        Storage::fake('local');
        $order = $this->order('pcs', ['design_mode' => 'upload', 'design' => UploadedFile::fake()->image('lama.jpg')]);
        $customer = auth()->user();
        $oldPath = $order->design_path;
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/orders/'.$order->id.'/review', ['action' => 'revision', 'admin_notes' => 'Gambar kurang jelas'])->assertRedirect();
        $this->actingAs($customer);
        $this->get('/pelanggan/orders/'.$order->id)->assertOk()->assertSee('Kirim perbaikan');
        $this->post('/pelanggan/orders/'.$order->id.'/revise', ['customer_notes' => 'Sudah diganti', 'design' => UploadedFile::fake()->image('baru.png')])->assertRedirect();
        $this->assertSame('pending', $order->fresh()->status);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($order->fresh()->design_path);
    }

    public function test_service_changes_do_not_change_existing_order_snapshot_and_cancel_is_preserved(): void
    {
        $order = $this->order('m2');
        $order->service->update(['name' => 'Nama baru', 'unit' => 'pcs', 'base_price' => 1]);
        $this->assertSame('m2', $order->fresh()->unit);
        $this->assertSame('225000.00', $order->fresh()->estimated_total);
        $this->post('/pelanggan/orders/'.$order->id.'/cancel')->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_customer_cannot_approve_a_price_that_has_changed(): void
    {
        $order = $this->order('pcs');
        $customer = auth()->user();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/orders/'.$order->id.'/review', ['action' => 'quote', 'final_total' => 2000])->assertRedirect();
        $this->actingAs($customer);
        $this->post('/pelanggan/orders/'.$order->id.'/approve', ['accepted_total' => 1000])->assertSessionHasErrors('action');
        $this->assertSame('quoted', $order->fresh()->status);
    }

    public function test_manager_and_guest_cannot_enter_order_workspaces(): void
    {
        $this->get('/pelanggan/orders')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'manajer']));
        $this->get('/pelanggan/orders')->assertForbidden();
        $this->get('/admin/orders')->assertForbidden();
    }
}
