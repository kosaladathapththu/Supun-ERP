<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class StockCount extends Model{protected $guarded=[];protected $casts=['count_date'=>'datetime','posted_at'=>'datetime'];public function location(){return $this->belongsTo(StockLocation::class,'stock_location_id');}public function items(){return $this->hasMany(StockCountItem::class);}}
