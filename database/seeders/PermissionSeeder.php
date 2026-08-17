<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['dashboard', 'sales', 'backdated_invoices', 'purchases', 'inventory', 'products', 'customers', 'suppliers', 'accounting', 'reports', 'imports', 'cashiers', 'users', 'roles', 'audit', 'periods', 'backups', 'settings'];
        $actions = ['view', 'create', 'update', 'post', 'reverse', 'approve', 'export'];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $slug = "$module.$action";
                DB::table('permissions')->updateOrInsert(['slug' => $slug], ['name' => Str::headline("$action $module"), 'module' => $module, 'description' => null, 'updated_at' => now(), 'created_at' => now()]);
            }
        }
        $permissions = DB::table('permissions')->get(['id', 'slug', 'module']);
        $profiles = [
            'main-admin' => $permissions->pluck('id'),
            'cfo' => $permissions->reject(fn ($p) => in_array($p->slug, ['users.create', 'roles.update', 'backdated_invoices.approve', 'backdated_invoices.update'], true))->pluck('id'),
            'manager' => $permissions->filter(fn ($p) => in_array($p->module, ['dashboard', 'sales', 'purchases', 'inventory', 'products', 'customers', 'suppliers', 'reports', 'imports', 'cashiers', 'audit', 'periods'], true))->pluck('id'),
            'cashier' => $permissions->filter(fn ($p) => in_array($p->slug, ['dashboard.view', 'sales.view', 'sales.create', 'sales.post', 'customers.view', 'customers.create', 'products.view', 'inventory.view', 'cashiers.view', 'cashiers.create', 'cashiers.post'], true))->pluck('id'),
            'storekeeper' => $permissions->filter(fn ($p) => in_array($p->module, ['dashboard', 'purchases', 'inventory', 'products', 'suppliers', 'imports'], true) && ! in_array($p->slug, ['purchases.approve', 'imports.approve'], true))->pluck('id'),
        ];
        foreach ($profiles as $roleSlug => $permissionIds) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }
}
