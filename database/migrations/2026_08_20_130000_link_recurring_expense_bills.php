<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('expenses', function (Blueprint $table) { $table->foreignId('previous_expense_id')->nullable()->after('account_id')->constrained('expenses')->nullOnDelete(); $table->string('billing_period', 50)->nullable()->after('due_date'); $table->index(['company_id', 'payee', 'account_id']); }); }
    public function down(): void { Schema::table('expenses', function (Blueprint $table) { $table->dropIndex(['company_id', 'payee', 'account_id']); $table->dropConstrainedForeignId('previous_expense_id'); $table->dropColumn('billing_period'); }); }
};
