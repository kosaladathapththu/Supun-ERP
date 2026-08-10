<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['serial_tracking'=>'boolean','is_active'=>'boolean','average_cost'=>'decimal:4','current_quantity'=>'decimal:4']; public function category(){return $this->belongsTo(ProductCategory::class,'product_category_id');} public function brand(){return $this->belongsTo(Brand::class);} public function unit(){return $this->belongsTo(Unit::class);} public function prices(){return $this->hasMany(ProductPrice::class);} }
