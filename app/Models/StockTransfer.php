<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class StockTransfer extends Model{protected $guarded=[];protected $casts=['transfer_date'=>'datetime'];public function fromLocation(){return $this->belongsTo(StockLocation::class,'from_location_id');}public function toLocation(){return $this->belongsTo(StockLocation::class,'to_location_id');}public function items(){return $this->hasMany(StockTransferItem::class);}}
