<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Service;
use App\Services\RupiahInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterDataController extends Controller
{
    public const UNITS = ['pcs' => 'Pcs', 'lembar' => 'Lembar', 'm' => 'Meter panjang', 'm2' => 'Meter persegi (m²)', 'roll' => 'Roll', 'kg' => 'Kilogram'];

    private function definition(Request $request): array
    {
        return $request->routeIs('admin.services.*') ? ['class' => Service::class, 'table' => 'services', 'label' => 'Layanan', 'key' => 'services', 'icon' => 'print'] : ['class' => Material::class, 'table' => 'materials', 'label' => 'Bahan', 'key' => 'materials', 'icon' => 'box'];
    }

    public function index(Request $request)
    {
        $meta = $this->definition($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $query = $meta['class']::query();
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
        }
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return view('masters.index', ['meta' => $meta, 'records' => $query->orderBy('name')->paginate(10)->withQueryString(), 'units' => self::UNITS]);
    }

    public function create(Request $request)
    {
        $meta = $this->definition($request);

        return view('masters.form', ['meta' => $meta, 'record' => new $meta['class'], 'units' => self::UNITS]);
    }

    public function edit(Request $request, string $id)
    {
        $meta = $this->definition($request);

        return view('masters.form', ['meta' => $meta, 'record' => $meta['class']::findOrFail($id), 'units' => self::UNITS]);
    }

    private function validated(Request $request, array $meta, ?string $id = null): array
    {
        $rules = ['code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique($meta['table'], 'code')->ignore($id)], 'name' => ['required', 'string', 'max:150'], 'unit' => ['required', Rule::in(array_keys(self::UNITS))], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean']];
        if ($meta['key'] === 'services') {
            $rules['minimum_quantity'] = ['sometimes', 'required', 'integer', 'min:1', 'max:1000000'];
            $rules['image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'];
            RupiahInput::prepare($request, 'base_price');
            $rules['base_price'] = ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'];
        }

        return $request->validate($rules, ['code.unique' => 'Kode sudah digunakan. Pilih kode lain.'], ['code' => 'kode', 'name' => 'nama', 'unit' => 'satuan', 'base_price' => 'harga acuan', 'description' => 'keterangan', 'is_active' => 'status']);
    }

    private function saveImage(Request $request, array $meta, array &$data): ?string
    {
        unset($data['image']);
        if ($meta['key'] !== 'services' || ! $request->hasFile('image')) {
            return null;
        }
        $path = $request->file('image')->store('services', 'public');
        if (! $path) {
            throw ValidationException::withMessages(['image' => 'Gambar gagal disimpan. Coba kembali.']);
        }
        $data['image_path'] = $path;

        return $path;
    }

    public function store(Request $request)
    {
        $meta = $this->definition($request);
        $data = $this->validated($request, $meta);
        $path = $this->saveImage($request, $meta, $data);
        try {
            $meta['class']::create($data);
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }

        return redirect()->route('admin.'.$meta['key'].'.index')->with('status', $meta['label'].' berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $meta = $this->definition($request);
        $record = $meta['class']::findOrFail($id);
        $data = $this->validated($request, $meta, $id);
        $oldPath = $record->image_path;
        $path = $this->saveImage($request, $meta, $data);
        try {
            $record->update($data);
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $error;
        }
        if ($path && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.'.$meta['key'].'.index')->with('status', $meta['label'].' berhasil diperbarui.');
    }
}
