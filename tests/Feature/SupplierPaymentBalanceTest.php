<?php

namespace Tests\Feature;

use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
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

    public function test_supplier_opening_payable_is_visible_and_paid_as_accounts_payable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $user->company_id,
            'code' => 'OPEN-PAY',
            'name' => 'Opening Payable Supplier',
            'opening_balance' => 100000,
            'opening_balance_date' => now()->subMonth()->toDateString(),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SupplierPaymentService::class);
        $position = $service->position($user->company_id, $supplierId);

        $this->assertSame('100000.00', $position['total_outstanding']);
        $this->assertSame('100000.00', $position['opening_outstanding']);
        $this->assertSame('0.00', $position['invoice_outstanding']);

        $payment = $service->post([
            'supplier_id' => $supplierId,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'amount' => 100000,
            'opening_balance_applied' => 100000,
            'allocations' => [],
        ], $user);

        $this->assertDatabaseHas('supplier_payments', [
            'id' => $payment->id,
            'allocated_amount' => 100000,
            'opening_balance_applied' => 100000,
            'unapplied_amount' => 0,
        ]);
        $this->assertSame('0.00', $service->position($user->company_id, $supplierId)['total_outstanding']);

        $journalEntryId = DB::table('journal_entries')
            ->where('source_type', SupplierPayment::class)
            ->where('source_id', $payment->id)
            ->value('id');
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journalEntryId,
            'account_id' => DB::table('accounts')->where('company_id', $user->company_id)->where('code', '2100')->value('id'),
            'debit' => 100000,
        ]);
        $this->assertDatabaseMissing('journal_lines', [
            'journal_entry_id' => $journalEntryId,
            'account_id' => DB::table('accounts')->where('company_id', $user->company_id)->where('code', '1150')->value('id'),
        ]);
    }
}
