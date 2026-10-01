<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\PaymentTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $delivery = 'pickup'): Order
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'pelanggan']);
        $this->actingAs($customer);
        $service = Service::create(['code' => 'TEST', 'name' => 'Simulasi', 'unit' => 'pcs', 'base_price' => 1000, 'is_active' => true, 'minimum_quantity' => 1]);
        $this->post('/pelanggan/orders', ['service_id' => $service->id, 'quantity' => 1, 'design_mode' => 'assistance', 'customer_notes' => 'Desain uji', 'delivery_method' => $delivery, 'delivery_address' => 'Alamat simulasi'])->assertRedirect();
        $order = Order::firstOrFail();
        $order->update(['status' => 'completed', 'final_total' => '1000.50']);

        return $order;
    }

    private function payload(string $amount = '1000.50'): array
    {
        return ['method' => 'qris_demo', 'submission_key' => (string) Str::uuid(), 'amount' => $amount, 'sender_name' => 'Pelanggan uji', 'paid_on' => now()->timezone('Asia/Jakarta')->toDateString(), 'proof' => UploadedFile::fake()->image('dummy.jpg')];
    }

    public function test_full_payment_unlocks_pickup(): void
    {
        $this->handover('pickup', 'collected');
    }

    public function test_full_payment_unlocks_shipping(): void
    {
        $this->handover('shipping', 'shipped');
    }

    private function handover(string $delivery, string $status): void
    {
        $order = $this->order($delivery);
        $customer = auth()->user();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload('400.25'))->assertSessionHasNoErrors()->assertRedirect();
        $payment = $order->payments()->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('1000.50', PaymentTotals::forOrder($order)['remaining']);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->actingAs($admin);
        $this->post('/admin/orders/'.$order->id.'/progress', ['status' => $status])->assertSessionHasErrors('status');
        $this->post('/admin/payments/'.$payment->id.'/review', ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('600.25', PaymentTotals::forOrder($order)['remaining']);
        $this->post('/admin/orders/'.$order->id.'/progress', ['status' => $status])->assertSessionHasErrors('status');
        $this->actingAs($customer);
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload('600.25'))->assertSessionHasNoErrors();
        $this->actingAs($admin);
        $this->post('/admin/payments/'.$order->payments()->reorder('payments.id', 'desc')->value('id').'/review', ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertTrue(PaymentTotals::forOrder($order)['is_paid']);
        $this->post('/admin/orders/'.$order->id.'/progress', ['status' => $status])->assertSessionHasNoErrors();
        $this->assertSame($status, $order->fresh()->status);
    }

    public function test_duplicate_submission_and_overpayment_are_prevented(): void
    {
        $order = $this->order();
        $payload = $this->payload('700');
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $payload)->assertSessionHasNoErrors();
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $order->payments()->count());
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload('301'))->assertSessionHasErrors('amount');
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_rejection_requires_reason_and_frees_pending_balance(): void
    {
        $order = $this->order();
        $customer = auth()->user();
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload())->assertSessionHasNoErrors();
        $payment = $order->payments()->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/payments/'.$payment->id.'/review', ['decision' => 'rejected'])->assertSessionHasErrors('review_note');
        $this->post('/admin/payments/'.$payment->id.'/review', ['decision' => 'rejected', 'review_note' => 'Bukti simulasi tidak cocok'])->assertSessionHasNoErrors();
        $this->post('/admin/payments/'.$payment->id.'/review', ['decision' => 'verified'])->assertSessionHasErrors('decision');
        $this->assertSame('1000.50', PaymentTotals::forOrder($order)['available']);
        $this->actingAs($customer);
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload())->assertSessionHasNoErrors();
    }

    public function test_proofs_and_print_are_private_and_role_scoped(): void
    {
        $order = $this->order();
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload())->assertSessionHasNoErrors();
        $payment = $order->payments()->firstOrFail();
        $this->get('/pelanggan/payments/'.$payment->id.'/proof')->assertOk()->assertDownload();
        $this->get('/pelanggan/orders/'.$order->id.'/print')->assertOk()->assertSee('Sisa tagihan')->assertSee('QRIS');
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $this->get('/pelanggan/payments')->assertDontSee($order->number);
        $this->get('/pelanggan/payments/'.$payment->id.'/proof')->assertNotFound();
        $this->get('/pelanggan/orders/'.$order->id.'/print')->assertNotFound();
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload())->assertNotFound();
        $this->post('/admin/payments/'.$payment->id.'/review', ['decision' => 'verified'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/orders/'.$order->id.'/print')->assertOk();
        $this->get('/admin/payments')->assertOk()->assertSee($order->number);
        $this->get('/admin/payments/'.$payment->id.'/proof')->assertOk();
    }

    public function test_payment_rejects_unapproved_orders_invalid_method_future_date_and_bad_proof(): void
    {
        $order = $this->order();
        $order->update(['status' => 'quoted']);
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $this->payload())->assertSessionHasErrors('amount');
        $order->update(['status' => 'completed']);
        $this->post('/pelanggan/orders/'.$order->id.'/payments', array_replace($this->payload(), ['method' => 'invalid']))->assertSessionHasErrors('method');
        $this->post('/pelanggan/orders/'.$order->id.'/payments', array_replace($this->payload(), ['paid_on' => now()->addDays(2)->toDateString()]))->assertSessionHasErrors('paid_on');
        $this->post('/pelanggan/orders/'.$order->id.'/payments', array_replace($this->payload(), ['proof' => UploadedFile::fake()->create('unsafe.html', 10, 'text/html')]))->assertSessionHasErrors('proof');
        $this->assertSame(0,$order->payments()->count());
    }
}
