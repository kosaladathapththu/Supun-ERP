<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\AdvancedInventoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvancedInventoryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_damage_and_stock_count_preserve_inventory_invariants(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        $c = $u->company_id;
        $main = StockLocation::where('company_id', $c)->first();
        $branch = StockLocation::create(['company_id' => $c, 'code' => 'BR1', 'name' => 'Branch 1', 'is_active' => 1]);
        $cat = DB::table('product_categories')->insertGetId(['company_id' => $c, 'code' => 'ADV', 'name' => 'Advanced Inventory', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $p = Product::create(['company_id' => $c, 'product_category_id' => $cat, 'unit_id' => DB::table('units')->where('code', 'PCS')->value('id'), 'item_code' => 'ADV-1', 'name' => 'Inventory Product', 'average_cost' => 50, 'current_quantity' => 10, 'minimum_stock' => 0, 'reorder_level' => 0, 'warranty_months' => 0, 'serial_tracking' => 0, 'is_active' => 1]);
        DB::table('stock_movements')->insert(['company_id' => $c, 'product_id' => $p->id, 'stock_location_id' => $main->id, 'created_by' => $u->id, 'movement_at' => now(), 'movement_type' => 'opening', 'reference_type' => 'test', 'reference_id' => 1, 'reference_number' => 'OPEN', 'quantity_in' => 10, 'quantity_out' => 0, 'balance_quantity' => 10, 'unit_cost' => 50, 'stock_value' => 500, 'created_at' => now(), 'updated_at' => now()]);
        $s = app(AdvancedInventoryService::class);
        $s->transfer(['from_location_id' => $main->id, 'to_location_id' => $branch->id, 'items' => [['product_id' => $p->id, 'quantity' => 3]]], $u);
        $this->assertEquals(10, (float) $p->fresh()->current_quantity);
        $this->assertEquals(7, (float) $s->locationQuantity($c, $main->id, $p->id));
        $this->assertEquals(3, (float) $s->locationQuantity($c, $branch->id, $p->id));
        $adjustment = $s->adjust(['stock_location_id' => $branch->id, 'adjustment_type' => 'damaged', 'reason' => 'Water damage', 'items' => [['product_id' => $p->id, 'quantity_change' => -2]]], $u);
        $this->assertEquals(8, (float) $p->fresh()->current_quantity);
        $entry = DB::table('journal_entries')->where('source_type', get_class($adjustment))->where('source_id', $adjustment->id)->first();
        $this->assertEquals(DB::table('journal_lines')->where('journal_entry_id', $entry->id)->sum('debit'), DB::table('journal_lines')->where('journal_entry_id', $entry->id)->sum('credit'));
        $count = $s->startCount($branch->id, 'Cycle count', $u);
        $item = $count->items()->where('product_id', $p->id)->first();
        $this->assertEquals(1, (float) $item->system_quantity);
        $s->postCount($count, [$item->id => 0], $u);
        $this->assertSame('posted', $count->fresh()->status);
        $this->assertEquals(7, (float) $p->fresh()->current_quantity);
        $this->assertEquals(0, (float) $s->locationQuantity($c, $branch->id, $p->id));
    }

    public function test_inventory_operation_pages_open_with_real_product_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $u = User::where('email', 'admin@supun-erp.local')->first();
        $cat = DB::table('product_categories')->insertGetId(['company_id' => $u->company_id, 'code' => 'PAGE', 'name' => 'Page Test', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Product::create(['company_id' => $u->company_id, 'product_category_id' => $cat, 'unit_id' => DB::table('units')->where('code', 'PCS')->value('id'), 'item_code' => 'PAGE-1', 'name' => 'Rendered Product', 'average_cost' => 10, 'current_quantity' => 1, 'minimum_stock' => 0, 'reorder_level' => 0, 'warranty_months' => 0, 'serial_tracking' => 0, 'is_active' => 1]);
        foreach (['/inventory-operations', '/inventory-operations/transfer', '/inventory-operations/adjustment', '/inventory-operations/count'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }
    }
}
