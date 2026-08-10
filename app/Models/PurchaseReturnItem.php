<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseReturnItem extends Model{protected $guarded=[];protected $casts=['quantity'=>'decimal:4','unit_cost'=>'decimal:4','line_total'=>'decimal:2'];public function product(){return $this->belongsTo(Product::class);}public function grnItem(){return $this->belongsTo(GoodsReceivedNoteItem::class,'goods_received_note_item_id');}}
