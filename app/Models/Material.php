<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'description', 'unit', 'is_active'])]
class Material extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
