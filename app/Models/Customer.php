<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['credit_enabled' => 'boolean', 'is_walk_in' => 'boolean', 'is_active' => 'boolean', 'opening_balance' => 'decimal:2'];

    public function scopeRegistered($query)
    {
        return $query->where('is_walk_in', false)->where('code', '!=', 'WALK-IN');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function receipts()
    {
        return $this->hasMany(CustomerReceipt::class);
    }
}
