<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SaleReturn extends Model{protected $guarded=[];protected $casts=['return_date'=>'datetime','posted_at'=>'datetime'];
public function items(){return $this->hasMany(SaleReturnItem::class);}
public function sale(){return $this->belongsTo(Sale::class);}
public function customer(){return $this->belongsTo(Customer::class);}
public function creditNote(){return $this->hasOne(CreditNote::class);} }
