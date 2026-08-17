<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockLocationSeeder extends Seeder
{
    public function run(): void
    {
        $company = DB::table('companies')->where('code', 'SUPUN')->value('id');
        DB::table('stock_locations')->updateOrInsert(['company_id' => $company, 'code' => 'MAIN'], ['name' => 'Main Store', 'is_default' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
