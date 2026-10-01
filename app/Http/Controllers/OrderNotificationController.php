<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderNotification;
use App\Services\OrderNotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderNotificationController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data = $request->validate(['submission_key' => ['required', 'uuid'], 'message' => ['required', 'string', 'max:5000'], 'sent_confirmed' => ['required', 'accepted']]);
        DB::transaction(function () use ($order, $request, $data) {
            $locked = Order::with('user')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $phone = OrderNotificationTemplate::phone($locked->user->phone);
            if (! $phone) {
                throw ValidationException::withMessages(['message' => 'Nomor WhatsApp pelanggan belum valid. Perbarui kontak terlebih dahulu.']);
            }
            $existing = OrderNotification::where('submission_key', $data['submission_key'])->first();
            if ($existing) {
                abort_unless($existing->order_id === $order->id && $existing->user_id === $request->user()->id, 409);

                return;
            }
            OrderNotification::create(['order_id' => $order->id, 'user_id' => $request->user()->id, 'submission_key' => $data['submission_key'], 'phone' => $phone, 'order_status' => $locked->status, 'message' => $data['message']]);
        });

        return back()->with('status', 'Catatan pengiriman manual disimpan. Ini bukan konfirmasi diterima atau dibaca pelanggan.');
    }
}
