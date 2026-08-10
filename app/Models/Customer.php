<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['credit_enabled'=>'boolean','is_walk_in'=>'boolean','is_active'=>'boolean','opening_balance'=>'decimal:2']; }
