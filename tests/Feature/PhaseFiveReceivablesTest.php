<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseFiveReceivablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected int $customerId;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'cfo@supun-erp.local')->firstOrFail();
        $this->actingAs($this->admin);
        $company = DB::table('companies')->value('id');
        $customerTypeId = DB::table('customer_types')->value('id');
        $this->customerId = Customer::create(['company_id' => $company, 'customer_type_id' => $customerTypeId, 'code' => 'AR-CUSTOMER', 'name' => 'Receivables Customer', 'credit_enabled' => 1, 'is_walk_in' => 0, 'is_active' => 1])->id;
        $category = DB::table('product_categories')->insertGetId(['company_id' => $company, 'code' => 'AR', 'name' => 'Receivables', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->product = Product::create(['company_id' => $company, 'product_category_id' => $category, 'unit_id' => DB::table('units')->where('code', 'PCS')->value('id'), 'item_code' => 'AR-001', 'name' => 'AR Test', 'average_cost' => 50, 'current_quantity' => 10, 'minimum_stock' => 0, 'reorder_level' => 0, 'warranty_months' => 0, 'serial_tracking' => 0, 'is_active' => 1]);
    }

    private function creditSale(): Sale
    {
        $this->post(route('sales.store'), ['customer_id' => $this->customerId, 'channel' => 'retail', 'payment_type' => 'credit', 'due_date' => now()->addDays(30)->toDateString(), 'paid_amount' => 0, 'payment_method' => 'cash', 'discount_amount' => 0, 'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100]]])->assertSessionHasNoErrors();

        return Sale::latest('id')->firstOrFail();
    }

    public function test_receipt_allocates_to_invoice_and_updates_balance(): void
    {
        $sale = $this->creditSale();
        $this->post(route('receivables.store'), ['customer_id' => $this->customerId, 'receipt_date' => now()->toDateString(), 'payment_method' => 'cash', 'amount' => 75, 'allocations' => [$sale->id => 75]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'paid_amount' => 75, 'balance_amount' => 25, 'payment_status' => 'partially_paid']);
        $this->assertDatabaseHas('customer_receipts', ['allocated_amount' => 75, 'unapplied_amount' => 0]);
        $this->assertDatabaseHas('customer_receipt_allocations', ['sale_id' => $sale->id, 'amount' => 75]);

        $receipt = CustomerReceipt::latest('id')->firstOrFail();
        $this->get(route('receivables.show', $receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_number);
    }

    public function test_unallocated_amount_is_kept_as_customer_advance(): void
    {
        $sale = $this->creditSale();
        $this->post(route('receivables.store'), ['customer_id' => $this->customerId, 'receipt_date' => now()->toDateString(), 'payment_method' => 'bank_transfer', 'amount' => 150, 'allocations' => [$sale->id => 100]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_receipts', ['amount' => 150, 'allocated_amount' => 100, 'unapplied_amount' => 50]);
        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_receipt_without_manual_lines_is_applied_to_oldest_invoice(): void
    {
        $sale = $this->creditSale();
        $this->post(route('receivables.store'), ['customer_id' => $this->customerId, 'receipt_date' => now()->toDateString(), 'payment_method' => 'cash', 'amount' => 60, 'allocations' => [$sale->id => 0]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_receipts', ['amount' => 60, 'allocated_amount' => 60, 'unapplied_amount' => 0]);
        $this->assertEquals(40, (float) $sale->fresh()->balance_amount);
    }

    public function test_receipt_can_be_deliberately_kept_as_advance(): void
    {
        $sale = $this->creditSale();
        $this->post(route('receivables.store'), ['customer_id' => $this->customerId, 'receipt_date' => now()->toDateString(), 'payment_method' => 'cash', 'amount' => 60, 'keep_unapplied' => 1, 'allocations' => [$sale->id => 0]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customer_receipts', ['amount' => 60, 'allocated_amount' => 0, 'unapplied_amount' => 60]);
        $this->assertEquals(100, (float) $sale->fresh()->balance_amount);
    }

    public function test_receipt_rejects_allocation_above_invoice_balance(): void
    {
        $sale = $this->creditSale();
        $this->from(route('receivables.create', ['customer_id' => $this->customerId]))->post(route('receivables.store'), ['customer_id' => $this->customerId, 'receipt_date' => now()->toDateString(), 'payment_method' => 'cash', 'amount' => 110, 'allocations' => [$sale->id => 110]])->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('customer_receipts', 0);
        $this->assertEquals(100, $sale->fresh()->balance_amount);
    }

    public function test_ledger_includes_payment_collected_when_invoice_was_created(): void
    {
        $sale = app(\App\Services\SalePostingService::class)->post([
            'customer_id' => $this->customerId,
            'channel' => 'retail',
            'payment_type' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'paid_amount' => 25,
            'payment_method' => 'cash',
            'discount_amount' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100]],
        ], $this->admin);

        $this->get(route('receivables.ledger', $this->customerId))
            ->assertOk()
            ->assertSee('Payment at invoice')
            ->assertSee('Rs. 25.00')
            ->assertSee('Rs. 75.00');

        $this->get(route('receivables.index'))
            ->assertOk()
            ->assertSee('Rs. 25.00')
            ->assertSee('Rs. 75.00');
    }
}
