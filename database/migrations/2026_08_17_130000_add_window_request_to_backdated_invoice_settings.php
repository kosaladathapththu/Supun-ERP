<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::table('backdated_invoice_settings',function(Blueprint $table){$table->unsignedSmallInteger('requested_days')->nullable();$table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();$table->timestamp('requested_at')->nullable();});} public function down():void{Schema::table('backdated_invoice_settings',function(Blueprint $table){$table->dropConstrainedForeignId('requested_by');$table->dropColumn(['requested_days','requested_at']);});} };
