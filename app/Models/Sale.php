<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Sale extends Model{protected $guarded=[];protected $casts=['sale_date'=>'datetime','due_date'=>'date'];public function items(){return $this->hasMany(SaleItem::class);}public function customer(){return $this->belongsTo(Customer::class);}public function payments(){return $this->hasMany(SalePayment::class);} }
