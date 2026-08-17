<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $user = DB::table('users')->where('email', 'admin@supun-erp.local')->first();
        if (! $user) {
            return;
        }
        $cfo = DB::table('roles')->where('company_id', $user->company_id)->where('slug', 'cfo')->first();
        if (! $cfo) {
            return;
        }
        DB::table('roles')->where('id', $cfo->id)->update(['description' => 'Chief Financial Officer — full ERP and administration access', 'updated_at' => now()]);
        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $cfo->id, 'permission_id' => $permissionId]);
        }
        DB::table('user_roles')->where('user_id', $user->id)->delete();
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $cfo->id]);
        DB::table('users')->where('id', $user->id)->update(['name' => 'Chief Financial Officer', 'updated_at' => now()]);
    }

    public function down(): void
    {
        $user = DB::table('users')->where('email', 'admin@supun-erp.local')->first();
        if (! $user) {
            return;
        }
        $admin = DB::table('roles')->where('company_id', $user->company_id)->where('slug', 'main-admin')->first();
        if (! $admin) {
            return;
        }
        DB::table('user_roles')->where('user_id', $user->id)->delete();
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $admin->id]);
        DB::table('users')->where('id', $user->id)->update(['name' => 'Main Administrator', 'updated_at' => now()]);
    }
};
