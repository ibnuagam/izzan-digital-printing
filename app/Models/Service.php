<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'description', 'unit', 'base_price', 'is_active', 'image_path', 'minimum_quantity'])]
class Service extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'base_price' => 'decimal:2'];
    }
}
