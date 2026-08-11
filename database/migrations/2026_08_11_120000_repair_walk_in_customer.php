<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Support\Facades\DB;
return new class extends Migration{
 public function up():void{$now=now();foreach(DB::table('companies')->pluck('id') as $companyId){$retailType=DB::table('customer_types')->where('company_id',$companyId)->where('code','RET')->value('id');DB::table('customers')->where('company_id',$companyId)->where('code','!=','WALK-IN')->update(['is_walk_in'=>false]);DB::table('customers')->updateOrInsert(['company_id'=>$companyId,'code'=>'WALK-IN'],['customer_type_id'=>$retailType,'name'=>'Walk-in Customer','business_name'=>null,'credit_enabled'=>false,'is_walk_in'=>true,'is_active'=>true,'updated_at'=>$now,'created_at'=>$now]);}}
 public function down():void{}
};
