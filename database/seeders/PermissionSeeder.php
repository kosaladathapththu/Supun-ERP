<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['dashboard', 'sales', 'purchases', 'inventory', 'products', 'customers', 'suppliers', 'accounting', 'reports', 'imports', 'cashiers', 'users', 'roles', 'audit', 'periods', 'backups', 'settings'];
        $actions = ['view', 'create', 'update', 'post', 'reverse', 'approve', 'export'];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $slug = "$module.$action";
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    ['name' => Str::headline("$action $module"), 'module' => $module, 'description' => null, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
        $adminRoleId = DB::table('roles')->where('slug', 'main-admin')->value('id');
        foreach (DB::table('permissions')->pluck('id') as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $adminRoleId, 'permission_id' => $permissionId]);
        }
    }
}
