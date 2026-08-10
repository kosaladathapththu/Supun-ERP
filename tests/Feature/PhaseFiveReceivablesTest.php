<?php
namespace Tests\Feature;
use App\Models\{CustomerReceipt,Product,Sale,User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class PhaseFiveReceivablesTest extends TestCase
{
    use RefreshDatabase;
    protected User $admin; protected int $customerId; protected Product $product;
    protected function setUp():void
    {
        parent::setUp();$this->seed(DatabaseSeeder::class);$this->admin=User::where('email','admin@supun-erp.local')->firstOrFail();$this->actingAs($this->admin);$company=DB::table('companies')->value('id');$this->customerId=DB::table('customers')->where('code','WALK-IN')->value('id');$category=DB::table('product_categories')->insertGetId(['company_id'=>$company,'code'=>'AR','name'=>'Receivables','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);$this->product=Product::create(['company_id'=>$company,'product_category_id'=>$category,'unit_id'=>DB::table('units')->where('code','PCS')->value('id'),'item_code'=>'AR-001','name'=>'AR Test','average_cost'=>50,'current_quantity'=>10,'minimum_stock'=>0,'reorder_level'=>0,'warranty_months'=>0,'serial_tracking'=>0,'is_active'=>1]);
    }
    private function creditSale():Sale
    {
        $this->post(route('sales.store'),['customer_id'=>$this->customerId,'channel'=>'retail','payment_type'=>'credit','due_date'=>now()->addDays(30)->toDateString(),'paid_amount'=>0,'payment_method'=>'cash','discount_amount'=>0,'items'=>[['product_id'=>$this->product->id,'quantity'=>1,'unit_price'=>100]]])->assertSessionHasNoErrors();return Sale::latest('id')->firstOrFail();
    }
    public function test_receipt_allocates_to_invoice_and_updates_balance():void
    {
        $sale=$this->creditSale();$this->post(route('receivables.store'),['customer_id'=>$this->customerId,'receipt_date'=>now()->toDateString(),'payment_method'=>'cash','amount'=>75,'allocations'=>[$sale->id=>75]])->assertSessionHasNoErrors();$this->assertDatabaseHas('sales',['id'=>$sale->id,'paid_amount'=>75,'balance_amount'=>25,'payment_status'=>'partially_paid']);$this->assertDatabaseHas('customer_receipts',['allocated_amount'=>75,'unapplied_amount'=>0]);$this->assertDatabaseHas('customer_receipt_allocations',['sale_id'=>$sale->id,'amount'=>75]);
    }
    public function test_unallocated_amount_is_kept_as_customer_advance():void
    {
        $sale=$this->creditSale();$this->post(route('receivables.store'),['customer_id'=>$this->customerId,'receipt_date'=>now()->toDateString(),'payment_method'=>'bank_transfer','amount'=>150,'allocations'=>[$sale->id=>100]])->assertSessionHasNoErrors();$this->assertDatabaseHas('customer_receipts',['amount'=>150,'allocated_amount'=>100,'unapplied_amount'=>50]);$this->assertSame('paid',$sale->fresh()->payment_status);
    }
    public function test_receipt_rejects_allocation_above_invoice_balance():void
    {
        $sale=$this->creditSale();$this->from(route('receivables.create',['customer_id'=>$this->customerId]))->post(route('receivables.store'),['customer_id'=>$this->customerId,'receipt_date'=>now()->toDateString(),'payment_method'=>'cash','amount'=>110,'allocations'=>[$sale->id=>110]])->assertSessionHasErrors('allocations');$this->assertDatabaseCount('customer_receipts',0);$this->assertEquals(100,$sale->fresh()->balance_amount);
    }
}
