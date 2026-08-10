<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class SupplierInvoice extends Model{protected $guarded=[];protected $casts=['invoice_date'=>'date','due_date'=>'date'];public function supplier(){return $this->belongsTo(Supplier::class);}public function grn(){return $this->belongsTo(GoodsReceivedNote::class,'goods_received_note_id');}}
