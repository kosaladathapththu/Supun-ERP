<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class SupplierPayment extends Model{protected $guarded=[];protected $casts=['payment_date'=>'date'];public function supplier(){return $this->belongsTo(Supplier::class);}public function allocations(){return $this->hasMany(SupplierPaymentAllocation::class);}}
