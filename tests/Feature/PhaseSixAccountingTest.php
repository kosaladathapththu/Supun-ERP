<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\JournalPostingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseSixAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Product $product;

    protected int $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $this->actingAs($this->admin);
        $c = $this->admin->company_id;
        $this->customer = DB::table('customers')->where('company_id', $c)->where('code', 'WALK-IN')->value('id');
        $cat = DB::table('product_categories')->insertGetId(['company_id' => $c, 'code' => 'GL', 'name' => 'GL', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->product = Product::create(['company_id' => $c, 'product_category_id' => $cat, 'unit_id' => DB::table('units')->where('code', 'PCS')->value('id'), 'item_code' => 'GL-1', 'name' => 'GL Test', 'average_cost' => 60, 'current_quantity' => 5, 'minimum_stock' => 0, 'reorder_level' => 0, 'warranty_months' => 0, 'serial_tracking' => 0, 'is_active' => 1]);
    }

    public function test_credit_sale_posts_balanced_revenue_inventory_and_ar_journal(): void
    {
        $this->post(route('sales.store'), ['customer_id' => $this->customer, 'channel' => 'retail', 'payment_type' => 'credit', 'due_date' => now()->addDays(30)->toDateString(), 'paid_amount' => 0, 'payment_method' => 'cash', 'discount_amount' => 0, 'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100]]])->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $entry = DB::table('journal_entries')->where('source_id', $sale->id)->first();
        $this->assertNotNull($entry);
        $totals = DB::table('journal_lines')->where('journal_entry_id', $entry->id)->selectRaw('SUM(debit) debit, SUM(credit) credit')->first();
        $this->assertEquals(160, $totals->debit);
        $this->assertEquals($totals->debit, $totals->credit);
        $this->assertDatabaseHas('journal_lines', ['account_id' => DB::table('accounts')->where('code', '1130')->value('id'), 'debit' => 100]);
    }

    public function test_receipt_reduces_ar_and_posts_advance_liability(): void
    {
        $this->post(route('sales.store'), ['customer_id' => $this->customer, 'channel' => 'retail', 'payment_type' => 'credit', 'due_date' => now()->addDays(30)->toDateString(), 'paid_amount' => 0, 'payment_method' => 'cash', 'discount_amount' => 0, 'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100]]]);
        $sale = Sale::firstOrFail();
        $this->post(route('receivables.store'), ['customer_id' => $this->customer, 'receipt_date' => now()->toDateString(), 'payment_method' => 'cash', 'amount' => 125, 'allocations' => [$sale->id => 100]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('journal_lines', ['account_id' => DB::table('accounts')->where('code', '1130')->value('id'), 'credit' => 100]);
        $this->assertDatabaseHas('journal_lines', ['account_id' => DB::table('accounts')->where('code', '2150')->value('id'), 'credit' => 25]);
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(JournalPostingService::class)->post($this->admin->company_id, now()->toDateString(), 'test', 999, 'TEST', 'Bad entry', [['account_code' => '1110', 'debit' => 10], ['account_code' => '4100', 'credit' => 9]], $this->admin->id);
    }

    public function test_closed_period_blocks_posting(): void
    {
        DB::table('accounting_periods')->whereDate('starts_on', '<=', now())->whereDate('ends_on', '>=', now())->update(['status' => 'closed']);
        $this->expectException(ValidationException::class);
        app(JournalPostingService::class)->post($this->admin->company_id, now()->toDateString(), 'test', 1000, 'TEST', 'Closed', [['account_code' => '1110', 'debit' => 10], ['account_code' => '4100', 'credit' => 10]], $this->admin->id);
    }
}
