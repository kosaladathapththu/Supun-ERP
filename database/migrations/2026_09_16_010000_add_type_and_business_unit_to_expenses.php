<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_type', 30)->default('general')->after('document_number')->index();
            $table->string('business_unit', 100)->default('Head Office')->after('expense_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['expense_type']);
            $table->dropIndex(['business_unit']);
            $table->dropColumn(['expense_type', 'business_unit']);
        });
    }
};
