<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerialNumber extends Model
{
    protected $guarded = [];
    protected $casts = [
        'warranty_starts_on' => 'date',
        'warranty_expires_on' => 'date',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function stockLocation() { return $this->belongsTo(StockLocation::class); }
    public function goodsReceivedNoteItem() { return $this->belongsTo(GoodsReceivedNoteItem::class); }
    public function saleItems() { return $this->belongsToMany(SaleItem::class, 'sale_item_serials'); }
}
