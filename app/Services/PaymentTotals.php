<?php

namespace App\Services;

use App\Models\Order;

class PaymentTotals
{
    public static function forOrder(Order $order): array
    {
        $verified = '0.00';
        $pending = '0.00';
        foreach ($order->payments()->whereIn('status', ['pending', 'verified'])->get(['amount', 'status']) as $payment) {
            if ($payment->status === 'verified') {
                $verified = bcadd($verified, $payment->amount, 2);
            } else {
                $pending = bcadd($pending, $payment->amount, 2);
            }
        }
        $remaining = $order->final_total !== null ? bcsub($order->final_total, $verified, 2) : null;
        $available = $remaining !== null ? bcsub($remaining, $pending, 2) : null;
        $paid = $remaining !== null && bccomp($remaining, '0', 2) <= 0;

        return ['verified' => $verified, 'pending' => $pending, 'remaining' => $remaining === null ? null : (bccomp($remaining, '0', 2) > 0 ? $remaining : '0.00'), 'available' => $available === null ? null : (bccomp($available, '0', 2) > 0 ? $available : '0.00'), 'is_paid' => $paid, 'label' => $paid ? 'Lunas' : (bccomp($verified, '0', 2) > 0 ? 'Dibayar sebagian' : 'Belum dibayar')];
    }
}
