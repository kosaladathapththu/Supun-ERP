<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceivedNote extends Model
{
    protected $guarded = [];

    protected $casts = ['received_date' => 'date', 'posted_at' => 'datetime', 'total_cost' => 'decimal:2'];

    public function items()
    {
        return $this->hasMany(GoodsReceivedNoteItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
