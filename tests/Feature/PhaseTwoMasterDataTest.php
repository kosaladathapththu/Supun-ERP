<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\ImportBatch;
use Tests\TestCase;

class PhaseTwoMasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_log_in_and_see_dashboard(): void
    {
        $this->post('/login', ['email' => 'admin@supun-erp.local', 'password' => 'ChangeMe!2026'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee('Business overview');
    }

    public function test_admin_can_create_master_data_and_product_with_price_history(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->firstOrFail();
        $this->actingAs($admin);
        $this->post(route('categories.store'), ['code' => 'MOB', 'name' => 'Mobile Phones', 'is_active' => 1])->assertRedirect(route('categories.index'));
        $categoryId = DB::table('product_categories')->where('code', 'MOB')->value('id');
        $unitId = DB::table('units')->where('code', 'PCS')->value('id');
        $this->post(route('products.store'), [
            'item_code'=>'PHONE-001','barcode'=>'890000000001','name'=>'Demo Phone','product_category_id'=>$categoryId,
            'unit_id'=>$unitId,'average_cost'=>50000,'retail_price'=>60000,'wholesale_price'=>57000,
            'minimum_stock'=>2,'reorder_level'=>5,'warranty_months'=>12,'serial_tracking'=>1,'is_active'=>1,
        ])->assertRedirect(route('products.index'));
        $productId = DB::table('products')->where('item_code', 'PHONE-001')->value('id');
        $this->assertDatabaseHas('products', ['id'=>$productId,'serial_tracking'=>1]);
        $this->assertDatabaseCount('product_prices', 2);
    }

    public function test_admin_can_create_customer_and_supplier(): void
    {
        $this->actingAs(User::where('email', 'admin@supun-erp.local')->firstOrFail());
        $this->post(route('customers.store'), ['code'=>'CUS-001','name'=>'Test Customer','phone'=>'0770000000','customer_type'=>'RET','default_due_term'=>'30_days','credit_enabled'=>1,'is_active'=>1])->assertSessionHasNoErrors();
        $this->post(route('suppliers.store'), ['code'=>'SUP-001','name'=>'Test Supplier','phone'=>'0110000000','is_active'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['code'=>'CUS-001','credit_enabled'=>1]);
        $this->assertDatabaseHas('suppliers', ['code'=>'SUP-001']);
    }

    public function test_admin_can_validate_preview_and_confirm_master_data_csv(): void
    {
        $this->actingAs(User::where('email', 'admin@supun-erp.local')->firstOrFail());
        $csv = implode("\n", [
            'supplier_code,supplier_name,supplier_phone,item_code,product_name,barcode,brand,unit,category,cost_price,retail_price,wholesale_price,minimum_stock,reorder_level,warranty_months,serial_tracking',
            'SUP-CSV,CSV Supplier,0111111111,CSV-001,Imported Phone,001234567890,Acme,PCS,Phones,1000,1250,1150,2,5,12,yes',
        ]);
        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);
        $response = $this->post(route('imports.store'), ['file'=>$file]);
        $batch = ImportBatch::firstOrFail();
        $response->assertRedirect(route('imports.show', $batch));
        $this->assertSame(1, $batch->valid_rows);
        $this->post(route('imports.confirm', $batch))->assertRedirect(route('imports.show', $batch));
        $this->assertDatabaseHas('products', ['item_code'=>'CSV-001','barcode'=>'001234567890']);
        $this->assertDatabaseHas('suppliers', ['code'=>'SUP-CSV']);
        $this->assertSame('imported', $batch->fresh()->status);
    }
}
