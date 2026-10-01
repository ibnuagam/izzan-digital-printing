<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentTotals;
use App\Services\RupiahInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private function authorizeOrder(Order $order): void
    {
        abort_unless(auth()->user()->role === 'admin' || $order->user_id === auth()->id(), 404);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::in(array_keys(Payment::STATUSES))]]);
        $query = Payment::with('order.user', 'reviewer');
        if ($request->user()->role === 'pelanggan') {
            $query->whereHas('order', fn ($q) => $q->where('user_id', auth()->id()));
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if ($q = trim($data['q'] ?? '')) {
            $query->whereHas('order', fn ($o) => $o->where('number', 'like', '%'.$q.'%'));
        }

        return view('payments.index', ['payments' => $query->latest('id')->paginate(10)->withQueryString()]);
    }

    public function store(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        RupiahInput::prepare($request, 'amount');
        $data = $request->validate(['method' => ['required', Rule::in(array_keys(config('printing.payment_methods')))], 'submission_key' => ['required', 'uuid'], 'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'], 'sender_name' => ['required', 'string', 'max:150'], 'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->timezone('Asia/Jakarta')->toDateString()], 'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'], 'customer_note' => ['nullable', 'string', 'max:2000']]);
        $path = null;
        try {
            DB::transaction(function () use ($request, $order, $data, &$path) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $existing = Payment::where('submission_key', $data['submission_key'])->first();
                if ($existing) {
                    abort_unless($existing->order_id === $locked->id && $existing->user_id === auth()->id(), 409);

                    return;
                }
                if (! in_array($locked->status, ['confirmed', 'processing', 'completed'], true) || $locked->final_total === null) {
                    throw ValidationException::withMessages(['amount' => 'Pembayaran hanya dapat diajukan setelah harga disetujui dan sebelum pesanan diserahkan.']);
                }
                $totals = PaymentTotals::forOrder($locked);
                if (bccomp((string) $data['amount'], $totals['available'], 2) > 0) {
                    throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa yang bisa diajukan. Bukti yang menunggu verifikasi juga diperhitungkan.']);
                }
                $path = $request->file('proof')->store('payment-proofs', 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['proof' => 'Bukti gagal disimpan. Coba kembali.']);
                }
                $payment = Payment::create(['order_id' => $locked->id, 'user_id' => auth()->id(), 'method' => $data['method'], 'method_label' => config('printing.payment_methods.'.$data['method'].'.label'), 'submission_key' => $data['submission_key'], 'amount' => $data['amount'], 'sender_name' => $data['sender_name'], 'paid_on' => $data['paid_on'], 'proof_path' => $path, 'customer_note' => $data['customer_note'] ?? null, 'status' => 'pending']);
                $locked->events()->create(['user_id' => auth()->id(), 'status' => $locked->status, 'note' => 'Bukti pembayaran #'.$payment->id.' diajukan: Rp '.number_format((float) $payment->amount, 2, ',', '.').'. Menunggu verifikasi admin.']);
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $error;
        }

        return redirect()->route('pelanggan.orders.show', $order)->with('status', 'Bukti pembayaran tercatat dan menunggu verifikasi admin.');
    }

    public function proof(Payment $payment)
    {
        $this->authorizeOrder($payment->order);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, $payment->order->number.'-bukti-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION));
    }

    public function review(Request $request, Payment $payment)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['verified', 'rejected'])], 'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($payment, $data) {
            // Semua perubahan pembayaran dan penyerahan memakai kunci pesanan yang sama.
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Bukti ini sudah diperiksa. Muat ulang halaman.']);
            }
            if ($data['decision'] === 'verified') {
                if (! in_array($order->status, ['confirmed', 'processing', 'completed'], true) || $order->final_total === null) {
                    throw ValidationException::withMessages(['decision' => 'Status pesanan tidak mengizinkan verifikasi pembayaran.']);
                }
                $totals = PaymentTotals::forOrder($order);
                if (bccomp(bcadd($totals['verified'], $locked->amount, 2), $order->final_total, 2) > 0) {
                    throw ValidationException::withMessages(['decision' => 'Pembayaran ini akan melebihi nilai pesanan.']);
                }
            }
            $locked->update(['status' => $data['decision'], 'review_note' => $data['review_note'] ?? null, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
            $order->events()->create(['user_id' => auth()->id(), 'status' => $order->status, 'note' => 'Bukti pembayaran #'.$locked->id.' '.($data['decision'] === 'verified' ? 'diverifikasi' : 'ditolak').': Rp '.number_format((float) $locked->amount, 2, ',', '.').'. '.($data['review_note'] ?? '')]);
        });

        return redirect()->route('admin.orders.show', $payment->order_id)->with('status', 'Hasil verifikasi pembayaran disimpan.');
    }
}
