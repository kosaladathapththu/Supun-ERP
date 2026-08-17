<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceivedNoteItem extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceivedNote()
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }
}
