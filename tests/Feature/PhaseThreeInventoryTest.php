<?php
namespace Tests\Feature;
use App\Models\{Product,PurchaseOrder,User};use Database\Seeders\DatabaseSeeder;use Illuminate\Foundation\Testing\RefreshDatabase;use Illuminate\Support\Facades\DB;use Tests\TestCase;
class PhaseThreeInventoryTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed(DatabaseSeeder::class);$this->actingAs(User::where('email','admin@supun-erp.local')->firstOrFail());}
 public function test_confirmed_purchase_order_can_be_partially_received_with_weighted_average_cost():void
 {
  $company=DB::table('companies')->value('id');$category=DB::table('product_categories')->insertGetId(['company_id'=>$company,'code'=>'TEST','name'=>'Test','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);$unit=DB::table('units')->where('code','PCS')->value('id');$supplier=DB::table('suppliers')->insertGetId(['company_id'=>$company,'code'=>'SUP-T','name'=>'Supplier T','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
  $product=Product::create(['company_id'=>$company,'product_category_id'=>$category,'unit_id'=>$unit,'item_code'=>'INV-001','name'=>'Inventory Test','average_cost'=>100,'current_quantity'=>10,'minimum_stock'=>0,'reorder_level'=>0,'warranty_months'=>0,'serial_tracking'=>0,'is_active'=>1]);
  $this->post(route('purchase-orders.store'),['supplier_id'=>$supplier,'order_date'=>'2026-08-10','items'=>[['product_id'=>$product->id,'quantity'=>4,'unit_cost'=>150]]])->assertSessionHasNoErrors();$order=PurchaseOrder::firstOrFail();$this->post(route('purchase-orders.confirm',$order))->assertSessionHasNoErrors();$item=$order->items()->first();$location=DB::table('stock_locations')->value('id');
  $this->post(route('grn.store',$order),['received_date'=>'2026-08-10','stock_location_id'=>$location,'items'=>[$item->id=>['quantity'=>2]]])->assertSessionHasNoErrors();
  $this->assertSame('partially_received',$order->fresh()->status);$this->assertEqualsWithDelta(12,(float)$product->fresh()->current_quantity,0.0001);$this->assertEqualsWithDelta(108.3333,(float)$product->fresh()->average_cost,0.0001);$this->assertDatabaseCount('stock_movements',1);$this->assertDatabaseCount('inventory_cost_history',1);
  $this->post(route('grn.store',$order),['received_date'=>'2026-08-10','stock_location_id'=>$location,'items'=>[$item->id=>['quantity'=>2]]])->assertSessionHasNoErrors();
  $this->assertSame('received',$order->fresh()->status);$this->assertEqualsWithDelta(14,(float)$product->fresh()->current_quantity,0.0001);$this->assertEqualsWithDelta(114.2856,(float)$product->fresh()->average_cost,0.0001);$this->assertDatabaseCount('stock_movements',2);$this->assertDatabaseCount('goods_received_notes',2);
 }
}
