<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerReceipt extends Model
{
    protected $guarded=[];
    protected $casts=['receipt_date'=>'datetime','amount'=>'decimal:2','allocated_amount'=>'decimal:2','unapplied_amount'=>'decimal:2'];
    public function customer(){return $this->belongsTo(Customer::class);}
    public function allocations(){return $this->hasMany(CustomerReceiptAllocation::class);}
}
