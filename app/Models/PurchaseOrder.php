<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class PurchaseOrder extends Model{use SoftDeletes;protected $guarded=[];protected $casts=['order_date'=>'date','expected_date'=>'date','subtotal'=>'decimal:2','total_amount'=>'decimal:2'];public function items(){return $this->hasMany(PurchaseOrderItem::class);}public function supplier(){return $this->belongsTo(Supplier::class);} }
