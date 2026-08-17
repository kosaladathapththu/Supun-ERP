<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $guarded = [];

    protected $casts = ['note_date' => 'datetime', 'amount' => 'decimal:2', 'applied_amount' => 'decimal:2'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }
}
