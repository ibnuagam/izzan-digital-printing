<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderNotificationTemplate;
use App\Services\RupiahInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RupiahAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $this->actingAs(User::factory()->create(['role' => 'pelanggan', 'phone' => '0812-3456-7890']));
        $service = Service::create(['code' => 'SIM', 'name' => 'Spanduk simulasi', 'unit' => 'pcs', 'base_price' => 250000, 'is_active' => true, 'minimum_quantity' => 1]);
        $this->post('/pelanggan/orders', ['service_id' => $service->id, 'quantity' => 1, 'design_mode' => 'assistance', 'customer_notes' => 'Uji', 'delivery_method' => 'pickup'])->assertRedirect();

        return Order::firstOrFail();
    }

    public function test_rupiah_formats_preserve_exact_values_and_reject_malformed_amounts(): void
    {
        foreach (['250000' => '250000', '250.000' => '250000', '250.000,00' => '250000.00', '250000,50' => '250000.50', 'Rp 250.000,50' => '250000.50', '250,000.50' => '250000.50', '250,000' => '250000', '0,01' => '0.01', '1000.50' => '1000.50'] as $input => $expected) {
            $this->assertSame($expected, RupiahInput::normalize($input));
        }
        foreach (['250,00000', '1e5', '12.34.567', '250.000,999', [], true, 'Rp abc'] as $input) {
            try {
                RupiahInput::normalize($input);
                $this->fail('Malformed amount accepted');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_localized_quote_and_transfer_save_correct_amount_and_reject_overpayment(): void
    {
        Storage::fake('local');
        $order = $this->order();
        $customer = auth()->user();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/orders/'.$order->id.'/review', ['action' => 'quote', 'final_total' => '250.000,50'])->assertSessionHasNoErrors();
        $this->assertSame('250000.50', $order->fresh()->final_total);
        $this->actingAs($customer);
        $this->post('/pelanggan/orders/'.$order->id.'/approve', ['accepted_total' => '250000.50'])->assertSessionHasNoErrors();
        $data = ['method' => 'bca_demo', 'submission_key' => (string) Str::uuid(), 'sender_name' => 'Uji', 'paid_on' => now()->timezone('Asia/Jakarta')->toDateString(), 'proof' => UploadedFile::fake()->image('uji.jpg')];
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $data + ['amount' => '250,00000'])->assertSessionHasErrors('amount');
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $data + ['amount' => '250.001'])->assertSessionHasErrors('amount');
        $this->post('/pelanggan/orders/'.$order->id.'/payments', $data + ['amount' => '250.000,50'])->assertSessionHasNoErrors();
        $this->assertSame('250000.50', $order->payments()->firstOrFail()->amount);
    }

    public function test_phone_and_templates_distinguish_unpaid_finished_order_from_ready_paid_order(): void
    {
        $this->assertSame('6281234567890', OrderNotificationTemplate::phone('0812-3456-7890'));
        $this->assertSame('6281234567890', OrderNotificationTemplate::phone('+62 812 3456 7890'));
        foreach ([null, '', '123', '+1 202 555 0100', '62abc123456789'] as $phone) {
            $this->assertNull(OrderNotificationTemplate::phone($phone));
        }
        $order = $this->order();
        $order->update(['status' => 'completed', 'final_total' => 250000]);
        $notice = OrderNotificationTemplate::forOrder($order);
        $this->assertStringContainsString('Mohon lunasi', $notice['message']);
        $this->assertStringNotContainsString('sudah lunas', $notice['message']);
        $order->payments()->create(['user_id' => $order->user_id, 'submission_key' => (string) Str::uuid(), 'method' => 'bca_demo', 'method_label' => 'BCA simulasi', 'amount' => 250000, 'sender_name' => 'Uji', 'paid_on' => now()->toDateString(), 'proof_path' => 'fixture.jpg', 'status' => 'verified']);
        $notice = OrderNotificationTemplate::forOrder($order);
        $this->assertStringContainsString('sudah lunas dan siap diambil', $notice['message']);
        $this->assertSame('Lunas', $notice['card']['payment']);
    }

    public function test_manual_log_requires_admin_confirmation_and_does_not_change_order_or_send_automatically(): void
    {
        $order = $this->order();
        $customer = auth()->user();
        $payload = ['submission_key' => (string) Str::uuid(), 'message' => 'Pesan simulasi telah dikirim manual', 'sent_confirmed' => 1];
        $this->post('/admin/orders/'.$order->id.'/notifications', $payload)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'manajer']));
        $this->post('/admin/orders/'.$order->id.'/notifications', $payload)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Buka WhatsApp')->assertSee('Unduh kartu JPG');
        $this->assertSame(0, $order->notifications()->count());
        $this->post('/admin/orders/'.$order->id.'/notifications', array_replace($payload, ['sent_confirmed' => 0]))->assertSessionHasErrors('sent_confirmed');
        $this->post('/admin/orders/'.$order->id.'/notifications', $payload)->assertSessionHasNoErrors();
        $this->post('/admin/orders/'.$order->id.'/notifications', $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $order->notifications()->count());
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('6281234567890', $order->notifications()->first()->phone);
        $this->actingAs($customer);
        $this->get('/pelanggan/orders/'.$order->id)->assertDontSee('notification-form', false)->assertDontSee('Pesan simulasi telah dikirim manual');
    }

    public function test_missing_phone_disables_whatsapp_and_rejects_manual_log(): void
    {
        $order = $this->order();
        $order->user->forceFill(['phone' => null])->save();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Nomor WhatsApp belum tersedia');
        $this->post('/admin/orders/'.$order->id.'/notifications', ['submission_key' => (string) Str::uuid(), 'message' => 'Uji', 'sent_confirmed' => 1])->assertSessionHasErrors('message');
        $this->assertSame(0,$order->notifications()->count());
    }
}
