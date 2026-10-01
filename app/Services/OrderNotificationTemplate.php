<?php

namespace App\Services;

use App\Models\Order;

class OrderNotificationTemplate
{
    public static function phone(?string $value): ?string
    {
        $number = preg_replace('/[\s()+-]/', '', $value ?? '');
        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }
        if (! preg_match('/^62[1-9][0-9]{7,12}$/D', $number)) {
            return null;
        }

        return $number;
    }

    public static function forOrder(Order $order): array
    {
        $totals = PaymentTotals::forOrder($order);
        $money = fn ($value) => $value === null ? 'Belum ditentukan' : 'Rp '.number_format((float) $value, 2, ',', '.');
        $status = Order::STATUSES[$order->status];
        $action = match ($order->status) {
            'pending' => 'Spesifikasi sedang diperiksa oleh admin.','revision' => 'Silakan lihat catatan perbaikan dan diskusikan melalui chat pesanan.','quoted' => 'Silakan periksa penawaran di aplikasi. Harga masih menunggu persetujuan Anda.','confirmed' => 'Harga sudah disetujui. Silakan ajukan bukti pembayaran melalui aplikasi.','processing' => 'Pesanan Anda sedang dikerjakan.','completed' => $totals['is_paid'] ? ($order->delivery_method === 'pickup' ? 'Pesanan sudah lunas dan siap diambil di toko.' : 'Pesanan sudah lunas dan siap dijadwalkan untuk pengiriman.') : 'Pengerjaan selesai. Mohon lunasi sisa tagihan sebelum diambil/dikirim.','shipped' => 'Pesanan sudah ditandai dikirim. Lihat catatan pengiriman di aplikasi.','collected' => 'Pesanan sudah ditandai diambil. Terima kasih.','cancelled' => 'Pesanan ini dibatalkan.',default => ''
        };
        $text = "[SIMULASI PENGEMBANGAN]\nHalo ".$order->user->name.",\nInformasi pesanan Izzan Digital Printing:\n\nNomor: ".$order->number."\nLayanan: ".$order->service_name."\nStatus: ".$status."\nHarga final: ".$money($order->final_total)."\nDiverifikasi: ".$money($totals['verified'])."\nSisa tagihan: ".$money($totals['remaining'])."\n\n".$action."\nBalas pesan ini atau hubungi admin melalui chat pesanan di aplikasi.\nMetode pembayaran saat ini dummy; jangan transfer uang nyata.";

        return ['phone' => self::phone($order->user->phone), 'message' => $text, 'card' => ['number' => $order->number, 'customer' => $order->user->name, 'service' => $order->service_name, 'quantity' => number_format($order->quantity, 0, ',', '.').' '.(in_array($order->unit, ['m', 'm2'], true) ? 'buah' : $order->unit), 'volume' => rtrim(rtrim($order->volume, '0'), '.').' '.($order->unit === 'm2' ? 'm²' : $order->unit), 'size' => $order->width ? (float) $order->width.($order->height ? ' × '.(float) $order->height : '').' m per barang' : 'Sesuai spesifikasi pesanan', 'status' => $status, 'delivery' => $order->delivery_method === 'pickup' ? 'Ambil di toko' : 'Kirim ke alamat', 'final' => $money($order->final_total), 'verified' => $money($totals['verified']), 'remaining' => $money($totals['remaining']), 'payment' => $totals['label'], 'action' => $action, 'date' => now()->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB']];
    }
}
