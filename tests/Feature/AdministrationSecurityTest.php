<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MainAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_staff_with_role_and_inactive_staff_cannot_login(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->first();
        $role = Role::where('slug', 'cashier')->first();
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Test Cashier', 'email' => 'cashier@example.test', 'phone' => '0771234567', 'password' => 'Temporary!2026', 'password_confirmation' => 'Temporary!2026', 'roles' => [$role->id], 'is_active' => 1])->assertRedirect(route('admin.users.index'));
        $staff = User::where('email', 'cashier@example.test')->first();
        $this->assertTrue($staff->roles()->where('roles.id', $role->id)->exists());
        $this->post('/logout');
        $this->post('/login', ['email' => $staff->email, 'password' => 'Temporary!2026'])->assertRedirect(route('password.edit'));
        $this->actingAs($admin)->put(route('admin.users.update', $staff), ['name' => $staff->name, 'email' => $staff->email, 'roles' => [$role->id]])->assertRedirect(route('admin.users.index'));
        $this->post('/logout');
        $this->post('/login', ['email' => $staff->email, 'password' => 'Temporary!2026'])->assertSessionHasErrors('email');
    }

    public function test_role_matrix_updates_and_main_admin_is_protected(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->first();
        $cashier = Role::where('slug', 'cashier')->first();
        $permission = Permission::where('slug', 'sales.view')->first();
        $this->actingAs($admin)->put(route('admin.roles.update', $cashier), ['name' => 'Cashier', 'permissions' => [$permission->id]])->assertRedirect(route('admin.roles.index'));
        $this->assertTrue($cashier->permissions()->where('permissions.id', $permission->id)->exists());
        $main = Role::where('slug', 'main-admin')->first();
        $this->actingAs($admin)->put(route('admin.roles.update', $main), ['name' => 'Changed', 'permissions' => []])->assertSessionHasErrors('role');
    }

    public function test_reseeding_does_not_reset_password_and_security_headers_are_present(): void
    {
        $admin = User::where('email', 'admin@supun-erp.local')->first();
        $admin->update(['password' => Hash::make('Private!Password2026'), 'password_changed_at' => now()]);
        $this->seed(MainAdminSeeder::class);
        $this->assertTrue(Hash::check('Private!Password2026', $admin->fresh()->password));
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
