<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            Schema::create('supplier_invoices', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $t->foreignId('goods_received_note_id')->nullable()->constrained()->restrictOnDelete();
                $t->string('document_number', 30);
                $t->string('supplier_invoice_number')->nullable();
                $t->date('invoice_date')->index();
                $t->date('due_date')->nullable()->index();
                $t->decimal('total_amount', 18, 2);
                $t->decimal('paid_amount', 18, 2)->default(0);
                $t->decimal('balance_amount', 18, 2);
                $t->string('payment_status', 20)->default('unpaid')->index();
                $t->string('status', 20)->default('posted')->index();
                $t->timestamps();
                $t->unique(['company_id', 'document_number']);
                $t->unique('goods_received_note_id');
            });
        }

        if (! Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
                $t->foreignId('paid_by')->constrained('users')->restrictOnDelete();
                $t->string('payment_number', 30);
                $t->date('payment_date')->index();
                $t->string('payment_method', 30);
                $t->decimal('amount', 18, 2);
                $t->decimal('allocated_amount', 18, 2);
                $t->decimal('unapplied_amount', 18, 2);
                $t->string('reference')->nullable();
                $t->string('status', 20)->default('posted');
                $t->timestamps();
                $t->unique(['company_id', 'payment_number']);
            });
        }

        if (! Schema::hasTable('supplier_payment_allocations')) {
            Schema::create('supplier_payment_allocations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('supplier_payment_id')->constrained()->cascadeOnDelete();
                $t->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
                $t->decimal('amount', 18, 2);
                $t->timestamps();
                $t->unique(
                    ['supplier_payment_id', 'supplier_invoice_id'],
                    'supplier_payment_invoice_unique'
                );
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $t) {
                $t->id();
                $t->foreignId('company_id')->constrained()->cascadeOnDelete();
                $t->foreignId('account_id')->constrained()->restrictOnDelete();
                $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $t->string('document_number', 30);
                $t->date('expense_date')->index();
                $t->string('payee');
                $t->string('payment_method', 30);
                $t->decimal('amount', 18, 2);
                $t->string('reference')->nullable();
                $t->text('description');
                $t->string('status', 20)->default('posted');
                $t->timestamps();
                $t->unique(['company_id', 'document_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoices');
    }
};
