<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $guarded = [];

    protected $casts = ['amount' => 'decimal:2', 'effective_from' => 'datetime', 'effective_until' => 'datetime', 'is_active' => 'boolean'];
}
