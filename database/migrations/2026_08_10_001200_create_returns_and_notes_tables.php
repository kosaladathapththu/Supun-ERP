<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->dateTime('return_date')->index();
            $table->string('return_type', 20)->default('partial');
            $table->string('settlement_type', 20)->default('credit_note');
            $table->string('status', 20)->default('posted')->index();
            $table->decimal('total_amount', 18, 2);
            $table->decimal('cost_total', 18, 2);
            $table->text('reason');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'document_number']);
        });
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('line_total', 18, 2);
            $table->decimal('cost_total', 18, 2);
            $table->string('condition', 20)->default('resalable');
            $table->timestamps();
        });
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_return_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->dateTime('note_date')->index();
            $table->decimal('amount', 18, 2);
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->string('status', 20)->default('available')->index();
            $table->text('reason');
            $table->timestamps();
            $table->unique(['company_id', 'document_number']);
        });
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_received_note_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->dateTime('return_date')->index();
            $table->string('status', 20)->default('posted')->index();
            $table->decimal('total_amount', 18, 2);
            $table->text('reason');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'document_number']);
        });
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_received_note_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('line_total', 18, 2);
            $table->string('reason_code', 30)->nullable();
            $table->timestamps();
        });
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_return_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->dateTime('note_date')->index();
            $table->decimal('amount', 18, 2);
            $table->decimal('applied_amount', 18, 2)->default(0);
            $table->string('status', 20)->default('available')->index();
            $table->text('reason');
            $table->timestamps();
            $table->unique(['company_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
