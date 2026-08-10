<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerReceiptAllocation extends Model
{
    protected $guarded=[];
    public function receipt(){return $this->belongsTo(CustomerReceipt::class,'customer_receipt_id');}
    public function sale(){return $this->belongsTo(Sale::class);}
}
