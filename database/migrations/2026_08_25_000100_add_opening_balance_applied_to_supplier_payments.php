<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('supplier_payments', 'opening_balance_applied')) {
            return;
        }

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->decimal('opening_balance_applied', 18, 2)->default(0)->after('allocated_amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('supplier_payments', 'opening_balance_applied')) {
            return;
        }

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropColumn('opening_balance_applied');
        });
    }
};
