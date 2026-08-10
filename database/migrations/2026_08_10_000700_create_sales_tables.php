<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('document_number',30); $table->dateTime('sale_date')->index(); $table->string('channel',20)->index(); $table->string('payment_type',20)->index(); $table->string('status',20)->default('posted')->index();
            $table->date('due_date')->nullable()->index(); $table->decimal('subtotal',18,2); $table->decimal('discount_amount',18,2)->default(0); $table->decimal('tax_amount',18,2)->default(0); $table->decimal('grand_total',18,2); $table->decimal('paid_amount',18,2)->default(0); $table->decimal('balance_amount',18,2)->default(0); $table->decimal('cost_total',18,2)->default(0); $table->decimal('gross_profit',18,2)->default(0); $table->string('payment_status',20)->index(); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['company_id','document_number']);
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('sale_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity',18,4); $table->decimal('unit_price',18,2); $table->decimal('unit_cost',18,4); $table->decimal('discount_amount',18,2)->default(0); $table->decimal('line_total',18,2); $table->decimal('cost_total',18,2); $table->decimal('gross_profit',18,2); $table->decimal('margin_percentage',9,4)->default(0); $table->timestamps();
        });
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('sale_id')->constrained()->restrictOnDelete(); $table->foreignId('received_by')->constrained('users')->restrictOnDelete(); $table->string('receipt_number',30); $table->dateTime('payment_date')->index(); $table->string('payment_method',30); $table->decimal('amount',18,2); $table->string('reference')->nullable(); $table->string('status',20)->default('posted'); $table->timestamps();
            $table->unique('receipt_number');
        });
        Schema::create('sale_item_serials', function (Blueprint $table) {
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_serial_number_id')->constrained()->restrictOnDelete(); $table->primary(['sale_item_id','product_serial_number_id']); $table->unique('product_serial_number_id');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sale_item_serials'); Schema::dropIfExists('sale_payments'); Schema::dropIfExists('sale_items'); Schema::dropIfExists('sales');
    }
};
