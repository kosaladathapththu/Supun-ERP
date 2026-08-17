<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BackdatedInvoiceRequest extends Model { protected $guarded=[]; protected $casts=['payload'=>'array','invoice_date'=>'date','submitted_at'=>'datetime','reviewed_at'=>'datetime']; public function requester(){return $this->belongsTo(User::class,'requested_by');} public function reviewer(){return $this->belongsTo(User::class,'reviewed_by');} public function sale(){return $this->belongsTo(Sale::class,'approved_sale_id');} }
