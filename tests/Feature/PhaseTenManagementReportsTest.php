<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ManagementReportService;
use App\Services\SalePostingService;
use App\Services\SaleReturnService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseTenManagementReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_metrics_reconcile_sales_returns_profit_and_inventory(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        $c = $u->company_id;
        $cat = DB::table('product_categories')->insertGetId(['company_id' => $c, 'code' => 'MI', 'name' => 'Management', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $p = Product::create(['company_id' => $c, 'product_category_id' => $cat, 'unit_id' => DB::table('units')->where('code', 'PCS')->value('id'), 'item_code' => 'MI-1', 'name' => 'Report Product', 'average_cost' => 50, 'current_quantity' => 10, 'minimum_stock' => 0, 'reorder_level' => 2, 'warranty_months' => 0, 'serial_tracking' => 0, 'is_active' => 1]);
        $customer = DB::table('customers')->where('code', 'WALK-IN')->value('id');
        $sale = app(SalePostingService::class)->post(['customer_id' => $customer, 'channel' => 'retail', 'payment_type' => 'cash', 'paid_amount' => 200, 'payment_method' => 'cash', 'discount_amount' => 0, 'items' => [['product_id' => $p->id, 'quantity' => 2, 'unit_price' => 100]]], $u);
        app(SaleReturnService::class)->post($sale, ['settlement_type' => 'credit_note', 'reason' => 'Report return', 'items' => [$sale->items()->first()->id => ['quantity' => 1, 'condition' => 'resalable']]], $u);
        $data = app(ManagementReportService::class)->dashboard($c, now()->toDateString(), now()->toDateString());
        $this->assertEquals(100, $data['kpis']['net_sales']);
        $this->assertEquals(50, $data['kpis']['gross_profit']);
        $this->assertEquals(1, $data['kpis']['units']);
        $this->assertEquals(100, $data['kpis']['returns']);
        $this->assertEquals(450, $data['kpis']['stock_value']);
        $row = $data['top_products']->first();
        $this->assertEquals(100, $row->sales);
        $this->assertEquals(50, $row->profit);
    }

    public function test_report_center_drilldowns_and_exports_open(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        foreach (['/reports', '/reports/profitability', '/reports/inventory'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }$this->actingAs($u)->get('/reports/profitability/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($u)->get('/reports/inventory/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
