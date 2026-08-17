<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleManagementController extends Controller
{
    public function index(Request $r)
    {
        return view('admin.roles.index', ['roles' => Role::withCount(['permissions', 'users'])->where('company_id', $r->user()->company_id)->orderBy('name')->get()]);
    }

    public function edit(Request $r, $role)
    {
        $role = Role::with('permissions')->where('company_id', $r->user()->company_id)->findOrFail($role);
        $permissions = Permission::orderBy('module')->orderBy('slug')->get()->groupBy('module');

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $r, $role)
    {
        $role = Role::where('company_id', $r->user()->company_id)->findOrFail($role);
        if ($role->slug === 'main-admin') {
            throw ValidationException::withMessages(['role' => 'The full-access CFO profile is protected.']);
        }$data = $r->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:500'], 'permissions' => ['nullable', 'array'], 'permissions.*' => ['integer', Rule::exists('permissions', 'id')]]);
        $role->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role permissions updated.');
    }
}
