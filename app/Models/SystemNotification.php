<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SystemNotification extends Model{protected $guarded=[];protected $casts=['read_at'=>'datetime'];}
