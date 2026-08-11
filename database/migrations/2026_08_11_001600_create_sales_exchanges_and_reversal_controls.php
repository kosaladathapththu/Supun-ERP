<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('reversed_by')->nullable()->after('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('payment_status');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });

        Schema::create('sales_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('sale_return_id')->constrained()->restrictOnDelete();
            $table->foreignId('replacement_sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('credit_note_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->dateTime('exchange_date')->index();
            $table->decimal('returned_amount', 18, 2);
            $table->decimal('replacement_amount', 18, 2);
            $table->decimal('credit_applied', 18, 2);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->string('status', 20)->default('posted')->index();
            $table->text('reason');
            $table->timestamps();
            $table->unique(['company_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_exchanges');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });
    }
};
