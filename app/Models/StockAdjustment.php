<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class StockAdjustment extends Model{protected $guarded=[];protected $casts=['adjustment_date'=>'datetime'];public function location(){return $this->belongsTo(StockLocation::class,'stock_location_id');}public function items(){return $this->hasMany(StockAdjustmentItem::class);}}
