<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const STATUSES = ['pending' => 'Menunggu pemeriksaan', 'revision' => 'Perlu perbaikan', 'quoted' => 'Menunggu persetujuan harga', 'confirmed' => 'Harga disetujui', 'processing' => 'Sedang diproses', 'completed' => 'Selesai, siap diambil/dikirim', 'shipped' => 'Sudah dikirim', 'collected' => 'Sudah diambil', 'cancelled' => 'Dibatalkan'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'width' => 'decimal:3', 'height' => 'decimal:3', 'volume' => 'decimal:4', 'unit_price' => 'decimal:2', 'estimated_total' => 'decimal:2', 'final_total' => 'decimal:2', 'quoted_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(OrderNotification::class)->orderByDesc('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(OrderMessage::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }
}
