<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class AccountingPeriod extends Model{protected $guarded=[];protected $casts=['starts_on'=>'date','ends_on'=>'date'];public function financialYear(){return $this->belongsTo(FinancialYear::class);}}
