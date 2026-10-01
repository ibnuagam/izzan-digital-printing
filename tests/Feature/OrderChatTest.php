<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderChatTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $customer = User::factory()->create(['role' => 'pelanggan']);
        $this->actingAs($customer);
        $service = Service::create(['code' => 'CHAT-'.uniqid(), 'name' => 'Cetak simulasi', 'unit' => 'pcs', 'base_price' => 1000, 'is_active' => true, 'minimum_quantity' => 1]);
        $this->post('/pelanggan/orders', ['service_id' => $service->id, 'quantity' => 1, 'design_mode' => 'assistance', 'customer_notes' => 'Desain simulasi', 'delivery_method' => 'pickup'])->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    private function payload(string $body = 'Boleh nego harga termasuk desain?'): array
    {
        return ['body' => $body, 'submission_key' => (string) Str::uuid()];
    }

    public function test_customer_and_admin_exchange_messages_without_changing_order_or_payments(): void
    {
        $order = $this->order();
        $customer = auth()->user();
        $before = $order->getAttributes();
        $this->get('/pelanggan/chat')->assertOk()->assertSee($order->number);
        $this->get('/pelanggan/chat/'.$order->id)->assertOk()->assertSee('Mulai percakapan');
        $this->post('/pelanggan/chat/'.$order->id, array_replace($this->payload(), ['sender_role' => 'admin', 'user_id' => 999, 'status' => 'verified']))->assertSessionHasNoErrors()->assertRedirect('/pelanggan/chat/'.$order->id);
        $first = $order->messages()->firstOrFail();
        $this->assertSame($customer->id, $first->user_id);
        $this->assertSame('pelanggan', $first->sender_role);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/chat')->assertOk()->assertSee('Boleh nego');
        $this->post('/admin/chat/'.$order->id, $this->payload('Bisa, harga termasuk desain akan dicatat pada penawaran.'))->assertSessionHasNoErrors();
        $this->get('/admin/chat/'.$order->id.'/feed')->assertOk()->assertJsonFragment(['latest_id' => $order->messages()->max('id')]);
        $this->actingAs($customer);
        $this->get('/pelanggan/chat/'.$order->id)->assertOk()->assertSee('harga termasuk desain');
        $this->assertSame(2, $order->messages()->count());
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame(0, $order->payments()->count());
    }

    public function test_other_customer_manager_and_guest_cannot_read_or_send_private_messages(): void
    {
        $order = $this->order();
        $this->post('/pelanggan/chat/'.$order->id, $this->payload('Pesan rahasia untuk pesanan saya'))->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $this->get('/pelanggan/chat')->assertDontSee($order->number)->assertDontSee('Pesan rahasia');
        $this->get('/pelanggan/chat/'.$order->id)->assertNotFound();
        $this->get('/pelanggan/chat/'.$order->id.'/feed')->assertNotFound();
        $this->post('/pelanggan/chat/'.$order->id, $this->payload())->assertNotFound();
        $this->get('/admin/chat')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'manajer']));
        $this->get('/admin/chat/'.$order->id)->assertForbidden();
        $this->get('/pelanggan/chat/'.$order->id.'/feed')->assertForbidden();
        $this->post('/admin/chat/'.$order->id, $this->payload())->assertForbidden();
        $this->post('/logout');
        $this->get('/admin/chat/'.$order->id)->assertRedirect('/login');
        $this->getJson('/pelanggan/chat/'.$order->id.'/feed')->assertUnauthorized();
        $this->assertSame(1, $order->messages()->count());
    }

    public function test_validation_escaping_and_duplicate_protection(): void
    {
        $order = $this->order();
        foreach (['', '   ', str_repeat('a', 3001), ['array']] as $body) {
            $this->post('/pelanggan/chat/'.$order->id, ['submission_key' => (string) Str::uuid(), 'body' => $body])->assertSessionHasErrors('body');
        }
        $this->post('/pelanggan/chat/'.$order->id, ['body' => 'Tanya harga', 'submission_key' => 'wrong'])->assertSessionHasErrors('submission_key');
        $payload = $this->payload('<script>alert(1)</script>');
        $this->post('/pelanggan/chat/'.$order->id, $payload)->assertSessionHasNoErrors();
        $this->post('/pelanggan/chat/'.$order->id, $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, $order->messages()->count());
        $this->get('/pelanggan/chat/'.$order->id)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $feed = $this->getJson('/pelanggan/chat/'.$order->id.'/feed')->assertOk();
        $this->assertStringContainsString('&lt;script&gt;', $feed->json('html'));
        $other = $this->order();
        $this->post('/pelanggan/chat/'.$other->id, $payload)->assertStatus(409);
        $this->assertSame(0, $other->messages()->count());
    }

    public function test_long_history_is_paginated_latest_first_and_search_is_scoped(): void
    {
        $order = $this->order();
        for ($i = 1; $i <= 55; $i++) {
            $order->messages()->create(['user_id' => $order->user_id, 'sender_role' => 'pelanggan', 'submission_key' => (string) Str::uuid(), 'body' => 'Simulasi pesan '.sprintf('%03d', $i)]);
        }
        $this->get('/pelanggan/chat/'.$order->id)->assertOk()->assertSee('Simulasi pesan 055')->assertDontSee('Simulasi pesan 001');
        $this->get('/pelanggan/chat/'.$order->id.'?page=2')->assertOk()->assertSee('Simulasi pesan 001')->assertDontSee('Simulasi pesan 055');
        $this->get('/pelanggan/chat?q='.$order->number)->assertSee($order->number);
        $this->get('/pelanggan/chat?q=tidakditemukan')->assertDontSee($order->number);
    }
}
