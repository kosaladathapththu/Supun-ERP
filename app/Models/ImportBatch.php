<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportBatch extends Model { protected $guarded=[]; protected $casts=['confirmed_at'=>'datetime','total_rows'=>'integer','valid_rows'=>'integer','invalid_rows'=>'integer']; public function rows(){return $this->hasMany(ImportRow::class);} public function getRouteKeyName(){return 'uuid';} }
