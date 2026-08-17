<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->date('order_date')->index();
            $table->date('expected_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'document_number']);
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2);
            $table->timestamps();
            $table->unique(['purchase_order_id', 'product_id']);
        });
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_location_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('document_number', 30);
            $table->string('supplier_invoice_number')->nullable()->index();
            $table->date('received_date')->index();
            $table->string('status', 20)->default('posted')->index();
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'document_number']);
        });
        Schema::create('goods_received_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('line_total', 18, 2);
            $table->decimal('average_cost_after', 18, 4);
            $table->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_location_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('movement_at')->index();
            $table->string('movement_type', 30)->index();
            $table->string('reference_type', 80);
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_number', 30)->index();
            $table->decimal('quantity_in', 18, 4)->default(0);
            $table->decimal('quantity_out', 18, 4)->default(0);
            $table->decimal('balance_quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('stock_value', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
            $table->index(['product_id', 'movement_at']);
        });
        Schema::create('inventory_cost_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_movement_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_before', 18, 4);
            $table->decimal('cost_before', 18, 4);
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('received_cost', 18, 4);
            $table->decimal('quantity_after', 18, 4);
            $table->decimal('cost_after', 18, 4);
            $table->timestamps();
        });
        Schema::table('product_serial_numbers', function (Blueprint $table) {
            $table->foreignId('stock_location_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_received_note_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unique('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('product_serial_numbers', function (Blueprint $table) {
        $table->dropUnique(['serial_number']);
        $table->dropConstrainedForeignId('goods_received_note_item_id');
        $table->dropConstrainedForeignId('stock_location_id');
        });
        Schema::dropIfExists('inventory_cost_history');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('goods_received_note_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('stock_locations');
    }
};
