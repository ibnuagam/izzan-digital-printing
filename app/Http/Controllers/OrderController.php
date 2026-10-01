<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Services\PaymentTotals;
use App\Services\RupiahInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private function authorizeOrder(Order $order): void
    {
        abort_unless(auth()->user()->role === 'admin' || $order->user_id === auth()->id(), 404);
    }

    private function assertState(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages(['action' => 'Status pesanan telah berubah atau tindakan ini tidak diizinkan. Muat ulang halaman.']);
        }
    }

    private function event(Order $order, string $status, ?string $note = null): void
    {
        $order->events()->create(['user_id' => auth()->id(), 'status' => $status, 'note' => $note]);
    }

    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))]]);
        $query = Order::with('user');
        if ($request->user()->role === 'pelanggan') {
            $query->where('user_id', $request->user()->id);
        }
        if ($q = trim($data['q'] ?? '')) {
            $query->where(fn ($q1) => $q1->where('number', 'like', '%'.$q.'%')->orWhere('service_name', 'like', '%'.$q.'%'));
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return view('orders.index', ['orders' => $query->latest('id')->paginate(10)->withQueryString(), 'statuses' => Order::STATUSES]);
    }

    public function create(Request $request)
    {
        return view('orders.create', ['services' => Service::where('is_active', true)->orderBy('name')->get(), 'selected' => $request->integer('service')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)], 'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'design_mode' => ['required', Rule::in(['upload', 'assistance'])], 'design' => ['required_if:design_mode,upload', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'customer_notes' => ['required_if:design_mode,assistance', 'nullable', 'string', 'max:3000'], 'delivery_method' => ['required', Rule::in(['pickup', 'shipping'])],
            'delivery_address' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:1000'],
        ]);
        $service = Service::findOrFail($data['service_id']);
        if ($data['quantity'] < $service->minimum_quantity) {
            throw ValidationException::withMessages(['quantity' => 'Minimal pesanan layanan ini adalah '.$service->minimum_quantity.'.']);
        }
        $dimensions = [];
        if (in_array($service->unit, ['m', 'm2'], true)) {
            $dimensions = $request->validate(['width' => ['required', 'numeric', 'min:0.001', 'max:1000', 'decimal:0,3']] + ($service->unit === 'm2' ? ['height' => ['required', 'numeric', 'min:0.001', 'max:1000', 'decimal:0,3']] : []));
        }
        $volume = (string) $data['quantity'];
        if ($service->unit === 'm') {
            $volume = bcmul($volume, (string) $dimensions['width'], 4);
        }
        if ($service->unit === 'm2') {
            $volume = bcmul(bcmul((string) $dimensions['width'], (string) $dimensions['height'], 6), $volume, 4);
        }
        if (bccomp($volume, '0', 4) <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Volume terlalu kecil. Periksa ukuran pesanan.']);
        }
        $estimate = $service->base_price !== null ? bcmul($volume, $service->base_price, 2) : null;
        if ($estimate !== null && bccomp($estimate, '9999999999999999.99', 2) > 0) {
            throw ValidationException::withMessages(['quantity' => 'Estimasi terlalu besar. Kurangi jumlah atau periksa harga layanan.']);
        }
        $path = $request->hasFile('design') ? $request->file('design')->store('order-designs', 'local') : null;
        if ($request->hasFile('design') && ! $path) {
            throw ValidationException::withMessages(['design' => 'Desain gagal disimpan. Coba kembali.']);
        }
        try {
            $order = DB::transaction(function () use ($data, $service, $dimensions, $volume, $path) {
                // Simpan nama, satuan, dan harga acuan saat pemesanan agar perubahan master tidak mengubah riwayat.
                $order = Order::create(['number' => 'IZN-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)), 'user_id' => auth()->id(), 'service_id' => $service->id, 'service_name' => $service->name, 'unit' => $service->unit, 'quantity' => $data['quantity'], 'width' => $dimensions['width'] ?? null, 'height' => $dimensions['height'] ?? null, 'volume' => $volume, 'unit_price' => $service->base_price, 'estimated_total' => $service->base_price !== null ? bcmul($volume, $service->base_price, 2) : null, 'design_mode' => $data['design_mode'], 'design_path' => $path, 'customer_notes' => $data['customer_notes'] ?? null, 'delivery_method' => $data['delivery_method'], 'delivery_address' => $data['delivery_method'] === 'shipping' ? $data['delivery_address'] : null, 'status' => 'pending']);
                $this->event($order, 'pending', 'Pesanan dibuat oleh pelanggan.');

                return $order;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect()->route('pelanggan.orders.show', $order)->with('status', 'Pesanan berhasil dibuat. Admin akan memeriksa desain dan menentukan harga final.');
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);

        return view('orders.show', ['order' => $order->load('user', 'events.user', 'payments.reviewer'), 'statuses' => Order::STATUSES, 'totals' => PaymentTotals::forOrder($order)]);
    }

    public function receipt(Order $order)
    {
        $this->authorizeOrder($order);

        return view('orders.print', ['order' => $order->load('user', 'payments'), 'totals' => PaymentTotals::forOrder($order)]);
    }

    public function design(Order $order)
    {
        $this->authorizeOrder($order);
        abort_unless($order->design_path && Storage::disk('local')->exists($order->design_path), 404);

        return Storage::disk('local')->download($order->design_path, $order->number.'-desain.'.pathinfo($order->design_path, PATHINFO_EXTENSION));
    }

    public function review(Request $request, Order $order)
    {
        RupiahInput::prepare($request, 'final_total');
        $data = $request->validate(['action' => ['required', Rule::in(['quote', 'revision'])], 'admin_notes' => ['required_if:action,revision', 'nullable', 'string', 'max:3000'], 'final_total' => ['required_if:action,quote', 'nullable', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2']]);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertState($locked, ['pending', 'revision', 'quoted']);
            $status = $data['action'] === 'quote' ? 'quoted' : 'revision';
            $locked->update(['status' => $status, 'admin_notes' => $data['admin_notes'] ?? null, 'final_total' => $status === 'quoted' ? $data['final_total'] : null, 'quoted_at' => $status === 'quoted' ? now() : null, 'approved_at' => null]);
            $this->event($locked, $status, $status === 'quoted' ? 'Harga final ditawarkan: Rp '.number_format((float) $data['final_total'], 2, ',', '.').'. '.($data['admin_notes'] ?? '') : $data['admin_notes']);
        });

        return back()->with('status', 'Hasil pemeriksaan telah disimpan.');
    }

    public function approve(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $data = $request->validate(['accepted_total' => ['required', 'numeric', 'min:0.01', 'decimal:0,2']]);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertState($locked, ['quoted']);
            abort_unless($locked->final_total !== null, 422);
            if (bccomp($locked->final_total, (string) $data['accepted_total'], 2) !== 0) {
                throw ValidationException::withMessages(['action' => 'Harga final telah berubah. Muat ulang halaman dan periksa penawaran terbaru.']);
            }$locked->update(['status' => 'confirmed', 'approved_at' => now()]);
            $this->event($locked, 'confirmed', 'Pelanggan menyetujui harga final: Rp '.number_format((float) $locked->final_total, 2, ',', '.'));
        });

        return back()->with('status', 'Harga disetujui. Admin dapat melanjutkan pengerjaan.');
    }

    public function cancel(Order $order)
    {
        $this->authorizeOrder($order);
        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertState($locked, ['pending', 'revision', 'quoted']);
            $locked->update(['status' => 'cancelled']);
            $this->event($locked, 'cancelled', 'Pesanan dibatalkan oleh pelanggan.');
        });

        return back()->with('status', 'Pesanan dibatalkan.');
    }

    public function revise(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $data = $request->validate(['customer_notes' => ['required', 'string', 'max:3000'], 'design' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048']]);
        $path = $request->hasFile('design') ? $request->file('design')->store('order-designs', 'local') : null;
        if ($request->hasFile('design') && ! $path) {
            throw ValidationException::withMessages(['design' => 'Desain gagal disimpan.']);
        }
        $oldPath = null;
        try {
            DB::transaction(function () use ($order, $data, $path, &$oldPath) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $this->assertState($locked, ['revision']);
                $oldPath = $locked->design_path;
                $changes = ['customer_notes' => $data['customer_notes'], 'status' => 'pending', 'final_total' => null, 'quoted_at' => null];
                if ($path) {
                    $changes['design_path'] = $path;
                }$locked->update($changes);
                $this->event($locked, 'pending', 'Pelanggan mengirim perbaikan: '.$data['customer_notes']);
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }
        if ($path && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('status', 'Perbaikan dikirim untuk diperiksa kembali.');
    }

    public function progress(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['processing', 'completed', 'shipped', 'collected'])], 'note' => ['nullable', 'string', 'max:3000']]);
        DB::transaction(function () use ($order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $next = match ($locked->status) {
                'confirmed' => ['processing'],'processing' => ['completed'],'completed' => [$locked->delivery_method === 'shipping' ? 'shipped' : 'collected'],default => []
            };
            if (! in_array($data['status'], $next, true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status tidak sesuai urutan atau metode penyerahan.']);
            }
            if (in_array($data['status'], ['shipped', 'collected'], true) && ! PaymentTotals::forOrder($locked)['is_paid']) {
                throw ValidationException::withMessages(['status' => 'Pesanan belum lunas. Verifikasi pelunasan sebelum pesanan diambil atau dikirim.']);
            }
            $locked->update(['status' => $data['status']]);
            $this->event($locked, $data['status'], $data['note'] ?? null);
        });

        return back()->with('status', 'Status pengerjaan diperbarui.');
    }
}
