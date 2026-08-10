<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SaleReturnItem extends Model{protected $guarded=[];public function product(){return $this->belongsTo(Product::class);}public function saleItem(){return $this->belongsTo(SaleItem::class);} }
