<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BackdatedInvoiceSetting extends Model { protected $guarded=[]; protected $casts=['temporary_until'=>'datetime']; public function activeDays():int{return $this->temporary_until?->isFuture()?(int)$this->temporary_days:7;} }
