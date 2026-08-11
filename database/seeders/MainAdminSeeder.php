<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MainAdminSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');
        $user = User::firstOrCreate(
            ['email' => 'admin@supun-erp.local'],
            ['company_id' => $companyId, 'name' => 'Main Administrator', 'password' => Hash::make('ChangeMe!2026'), 'is_active' => true]
        );
        $roleId = DB::table('roles')->where('company_id', $companyId)->where('slug', 'main-admin')->value('id');
        DB::table('user_roles')->updateOrInsert(['user_id' => $user->id, 'role_id' => $roleId]);
    }
}
