<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'cfo')->value('id');
        if (! $roleId) {
            return;
        }
        DB::table('role_permissions')->where('role_id', $roleId)->delete();
        foreach (DB::table('permissions')->whereNotIn('slug', ['users.create', 'roles.update'])->pluck('id') as $permissionId) {
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
    }
};
