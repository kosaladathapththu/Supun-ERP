<?php

namespace Tests\Feature;

use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\SupplierPaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplierPaymentBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_reduces_current_balance_without_reversing_a_debit_note(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $user->company_id, 'code' => 'PAY-RET', 'name' => 'Payment Return Supplier',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $invoice = SupplierInvoice::create([
            'company_id' => $user->company_id, 'supplier_id' => $supplierId, 'document_number' => 'PI-PAY-RET',
            'invoice_date' => now(), 'due_date' => now()->addMonth(), 'total_amount' => 500,
            'paid_amount' => 0, 'balance_amount' => 400, 'payment_status' => 'partially_paid', 'status' => 'posted',
        ]);

        app(SupplierPaymentService::class)->post([
            'supplier_id' => $supplierId, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
            'amount' => 100, 'allocations' => [$invoice->id => 100],
        ], $user);

        $this->assertDatabaseHas('supplier_invoices', [
            'id' => $invoice->id, 'paid_amount' => 100, 'balance_amount' => 300,
        ]);
    }
}
