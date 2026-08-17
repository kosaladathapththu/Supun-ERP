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
        $user = User::whereIn('email', ['cfo@supun-erp.local', 'admin@supun-erp.local'])->first();
        if (! $user) {
            $user = User::create(['company_id' => $companyId, 'name' => 'Chief Financial Officer', 'email' => 'cfo@supun-erp.local', 'password' => Hash::make('Cfo@Supun2026!'), 'is_active' => true]);
        }
        $user->update(['name' => 'Chief Financial Officer', 'email' => 'cfo@supun-erp.local']);
        $roleId = DB::table('roles')->where('company_id', $companyId)->where('slug', 'main-admin')->value('id');
        DB::table('user_roles')->where('user_id', $user->id)->delete();
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
    }
}
