<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backdated_invoice_settings', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('temporary_days')->nullable(); $table->timestamp('temporary_until')->nullable();
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('backdated_invoice_requests', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users'); $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('request_number')->unique(); $table->date('invoice_date'); $table->json('payload');
            $table->decimal('total_amount',18,2); $table->string('status',20)->default('pending');
            $table->text('review_note')->nullable(); $table->timestamp('submitted_at'); $table->timestamp('reviewed_at')->nullable(); $table->timestamps();
            $table->index(['company_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('backdated_invoice_requests'); Schema::dropIfExists('backdated_invoice_settings'); }
};
