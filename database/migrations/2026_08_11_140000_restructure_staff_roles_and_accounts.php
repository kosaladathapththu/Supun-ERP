<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $companyId = DB::table('companies')->where('code', 'SUPUN')->value('id');
        if (! $companyId) {
            return;
        }

        DB::table('roles')->where('company_id', $companyId)->where('slug', 'main-admin')->update([
            'name' => 'CFO', 'description' => 'Chief Financial Officer - protected full system access', 'updated_at' => now(),
        ]);
        DB::table('roles')->where('company_id', $companyId)->where('slug', 'cfo')->update([
            'name' => 'Accountant', 'description' => 'Accounting and operational access without staff or role administration', 'updated_at' => now(),
        ]);

        $permissions = DB::table('permissions')->get(['id', 'slug', 'module']);
        $profiles = [
            'main-admin' => $permissions->pluck('id'),
            'cfo' => $permissions->reject(fn ($p) => in_array($p->slug, ['users.create', 'users.update', 'roles.create', 'roles.update'], true))->pluck('id'),
            'manager' => $permissions->filter(fn ($p) => in_array($p->module, ['dashboard', 'sales', 'purchases', 'inventory', 'products', 'customers', 'suppliers', 'reports', 'imports', 'cashiers', 'audit', 'periods'], true))->pluck('id'),
            'cashier' => $permissions->filter(fn ($p) => in_array($p->slug, ['dashboard.view', 'sales.view', 'sales.create', 'sales.post', 'customers.view', 'customers.create', 'products.view', 'inventory.view', 'cashiers.view', 'cashiers.create', 'cashiers.post'], true))->pluck('id'),
            'storekeeper' => $permissions->filter(fn ($p) => in_array($p->module, ['dashboard', 'purchases', 'inventory', 'products', 'suppliers', 'imports'], true) && ! in_array($p->slug, ['purchases.approve', 'imports.approve'], true))->pluck('id'),
        ];
        foreach ($profiles as $slug => $permissionIds) {
            $roleId = DB::table('roles')->where('company_id', $companyId)->where('slug', $slug)->value('id');
            if (! $roleId) {
                continue;
            }
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        $accounts = [
            ['Chief Financial Officer', 'cfo@supun-erp.local', 'Cfo@Supun2026!', 'main-admin', ['admin@supun-erp.local', 'cfo@supun-erp.local']],
            ['Accountant', 'accountant@supun-erp.local', 'Accounts@Supun2026!', 'cfo', ['accountant@supun-erp.local']],
            ['Operations Manager', 'manager@supun-erp.local', 'Manager@Supun2026!', 'manager', ['manager@supun-erp.local']],
            ['Cashier', 'cashier@supun-erp.local', 'Cashier@Supun2026!', 'cashier', ['cashier@supun-erp.local']],
            ['Storekeeper', 'storekeeper@supun-erp.local', 'Stores@Supun2026!', 'storekeeper', ['storekeeper@supun-erp.local']],
        ];
        foreach ($accounts as [$name, $email, $password, $roleSlug, $lookupEmails]) {
            $userId = DB::table('users')->where('company_id', $companyId)->whereIn('email', $lookupEmails)->value('id');
            $values = ['company_id' => $companyId, 'name' => $name, 'email' => $email, 'password' => Hash::make($password), 'is_active' => true, 'password_changed_at' => null, 'updated_at' => now()];
            if ($userId) {
                DB::table('users')->where('id', $userId)->update($values);
            } else {
                $userId = DB::table('users')->insertGetId($values + ['created_at' => now()]);
            }
            $roleId = DB::table('roles')->where('company_id', $companyId)->where('slug', $roleSlug)->value('id');
            DB::table('user_roles')->where('user_id', $userId)->delete();
            DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
    }
};
