<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');
        foreach ([
            ['CFO', 'main-admin', 'Chief Financial Officer - protected full system access'],
            ['Accountant', 'cfo', 'Accounting and operational access without staff or role administration'],
            ['Manager', 'manager', 'Operational management and approvals'],
            ['Cashier', 'cashier', 'POS, receipts and cashier closing'],
            ['Storekeeper', 'storekeeper', 'Inventory, receiving and stock control'],
        ] as [$name, $slug, $description]) {
            DB::table('roles')->updateOrInsert(['company_id' => $companyId, 'slug' => $slug], compact('name', 'description') + ['is_system' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
    }
}
