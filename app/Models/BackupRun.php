<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class BackupRun extends Model{protected $guarded=[];protected $casts=['completed_at'=>'datetime'];}
