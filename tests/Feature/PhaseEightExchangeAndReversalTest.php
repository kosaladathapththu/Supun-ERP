<?php
namespace Tests\Feature;

use App\Models\{Product, Sale, User};
use App\Services\{SalePostingService, SalesExchangeService};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseEightExchangeAndReversalTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Product $oldProduct;
    private Product $newProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->user = User::where('email','admin@supun-erp.local')->firstOrFail();
        $category = DB::table('product_categories')->insertGetId(['company_id'=>$this->user->company_id,'code'=>'EXCH','name'=>'Exchange Tests','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $unit = DB::table('units')->where('code','PCS')->value('id');
        $base = ['company_id'=>$this->user->company_id,'product_category_id'=>$category,'unit_id'=>$unit,'average_cost'=>50,'current_quantity'=>10,'minimum_stock'=>0,'reorder_level'=>0,'warranty_months'=>0,'serial_tracking'=>0,'is_active'=>1];
        $this->oldProduct = Product::create($base+['item_code'=>'EX-OLD','name'=>'Old Product']);
        $this->newProduct = Product::create($base+['item_code'=>'EX-NEW','name'=>'New Product','average_cost'=>70]);
    }

    private function unpaidSale(): Sale
    {
        return app(SalePostingService::class)->post(['customer_id'=>DB::table('customers')->where('code','WALK-IN')->value('id'),'channel'=>'retail','payment_type'=>'credit','due_date'=>now()->addDays(30)->toDateString(),'paid_amount'=>0,'payment_method'=>'cash','discount_amount'=>0,'items'=>[['product_id'=>$this->oldProduct->id,'quantity'=>1,'unit_price'=>100]]],$this->user);
    }

    public function test_exchange_returns_old_stock_posts_replacement_and_applies_credit(): void
    {
        $sale = $this->unpaidSale();
        $this->actingAs($this->user)->get(route('sales-exchanges.create',$sale))->assertOk()->assertSee('Product Exchange');
        $exchange = app(SalesExchangeService::class)->post($sale,['reason'=>'Customer requested upgraded model','returned_items'=>[$sale->items()->first()->id=>['quantity'=>1,'condition'=>'resalable']],'replacement_items'=>[['product_id'=>$this->newProduct->id,'quantity'=>1,'unit_price'=>150]]],$this->user);

        $this->assertEquals(10,(float)$this->oldProduct->fresh()->current_quantity);
        $this->assertEquals(9,(float)$this->newProduct->fresh()->current_quantity);
        $this->assertEquals(100,(float)$exchange->credit_applied);
        $this->assertEquals(50,(float)$exchange->balance_due);
        $this->assertEquals(50,(float)$exchange->replacementSale->balance_amount);
        $this->assertSame('applied',$exchange->creditNote->status);
        $this->actingAs($this->user)->get(route('sales-exchanges.show',$exchange))->assertOk()->assertSee($exchange->document_number);
    }

    public function test_void_restores_stock_marks_invoice_and_balances_reversal(): void
    {
        $sale = $this->unpaidSale();
        $return = app(SalesExchangeService::class)->void($sale,'Duplicate invoice entered',$this->user);

        $this->assertSame('voided',$sale->fresh()->status);
        $this->assertSame('void',$return->fresh()->return_type);
        $this->assertEquals(10,(float)$this->oldProduct->fresh()->current_quantity);
        $entry = DB::table('journal_entries')->where('source_type',get_class($return))->where('source_id',$return->id)->first();
        $this->assertEquals(DB::table('journal_lines')->where('journal_entry_id',$entry->id)->sum('debit'),DB::table('journal_lines')->where('journal_entry_id',$entry->id)->sum('credit'));
        $this->actingAs($this->user)->get(route('sales.show',$sale))->assertOk()->assertSee('VOIDED');
    }
}
