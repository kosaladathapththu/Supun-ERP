<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('expense_date')->index();
            $table->decimal('paid_amount', 18, 2)->default(0)->after('amount');
            $table->decimal('balance_amount', 18, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 20)->default('unpaid')->after('balance_amount')->index();
        });
        DB::table('expenses')->update(['paid_amount' => DB::raw('amount'), 'balance_amount' => 0, 'payment_status' => 'paid']);
        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->string('payment_number', 30);
            $table->date('payment_date')->index();
            $table->string('payment_method', 30);
            $table->decimal('amount', 18, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'payment_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_payments');
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'paid_amount', 'balance_amount', 'payment_status']);
        });
    }
};
