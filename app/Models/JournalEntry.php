<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class JournalEntry extends Model{protected $guarded=[];protected $casts=['entry_date'=>'date'];public function lines(){return $this->hasMany(JournalLine::class);}public function period(){return $this->belongsTo(AccountingPeriod::class,'accounting_period_id');}}
