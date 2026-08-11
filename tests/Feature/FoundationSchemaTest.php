<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_schema_seeds_required_security_and_accounting_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['companies', 'users', 'roles', 'permissions', 'financial_years', 'accounting_periods', 'account_types', 'accounts', 'customers', 'suppliers', 'product_categories', 'brands', 'units', 'products', 'product_prices', 'product_serial_numbers'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertSame(5, DB::table('roles')->count());
        $this->assertSame(119, DB::table('permissions')->count());
        $this->assertSame(35, DB::table('accounts')->count());
        $this->assertSame(12, DB::table('accounting_periods')->count());
        $this->assertDatabaseHas('customers', ['code' => 'WALK-IN', 'is_walk_in' => true]);
        $admin = DB::table('users')->where('email', 'admin@supun-erp.local')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('ChangeMe!2026', $admin->password));
        $this->assertSame(119, DB::table('role_permissions')->count());
    }

    public function test_foundation_foreign_keys_are_declared(): void
    {
        $expected = [
            'users' => ['company_id'],
            'accounts' => ['company_id', 'account_type_id', 'parent_id'],
            'customers' => ['company_id', 'customer_type_id'],
            'products' => ['company_id', 'product_category_id', 'brand_id', 'unit_id'],
            'product_prices' => ['product_id', 'created_by'],
            'product_serial_numbers' => ['product_id'],
        ];

        foreach ($expected as $table => $columns) {
            $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('{$table}')"))->pluck('from')->all();
            foreach ($columns as $column) {
                $this->assertContains($column, $foreignKeys, "Missing FK {$table}.{$column}");
            }
        }
    }
}
