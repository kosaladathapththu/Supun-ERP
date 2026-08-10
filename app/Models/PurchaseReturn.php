<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseReturn extends Model{protected $guarded=[];protected $casts=['return_date'=>'datetime','posted_at'=>'datetime','total_amount'=>'decimal:2'];public function grn(){return $this->belongsTo(GoodsReceivedNote::class,'goods_received_note_id');}public function supplier(){return $this->belongsTo(Supplier::class);}public function items(){return $this->hasMany(PurchaseReturnItem::class);}public function debitNote(){return $this->hasOne(DebitNote::class);}}
