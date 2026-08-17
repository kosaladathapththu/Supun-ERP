<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesExchange extends Model
{
    protected $guarded = [];

    protected $casts = ['exchange_date' => 'datetime'];

    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function replacementSale()
    {
        return $this->belongsTo(Sale::class, 'replacement_sale_id');
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }
}
