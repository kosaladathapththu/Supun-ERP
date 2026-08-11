<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffAccountSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');
        foreach ([
            ['Accountant', 'accountant@supun-erp.local', 'Accounts@Supun2026!', 'cfo'],
            ['Operations Manager', 'manager@supun-erp.local', 'Manager@Supun2026!', 'manager'],
            ['Cashier', 'cashier@supun-erp.local', 'Cashier@Supun2026!', 'cashier'],
            ['Storekeeper', 'storekeeper@supun-erp.local', 'Stores@Supun2026!', 'storekeeper'],
        ] as [$name, $email, $password, $roleSlug]) {
            $user = User::firstOrCreate(['email' => $email], ['company_id' => $companyId, 'name' => $name, 'password' => Hash::make($password), 'is_active' => true, 'password_changed_at' => null]);
            $roleId = DB::table('roles')->where('company_id', $companyId)->where('slug', $roleSlug)->value('id');
            DB::table('user_roles')->where('user_id', $user->id)->delete();
            DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
        }
    }
}
