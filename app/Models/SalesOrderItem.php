<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SalesOrderItem extends Model{protected $guarded=[];public function product(){return $this->belongsTo(Product::class);}public function order(){return $this->belongsTo(SalesOrder::class,'sales_order_id');}}
