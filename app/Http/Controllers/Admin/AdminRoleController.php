<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = AdminRole::withCount('users')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $permissionKeys = implode(',', array_keys(AdminRole::ALL_PERMISSIONS));

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100', 'unique:admin_roles,name'],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', "in:{$permissionKeys}"],
        ]);

        AdminRole::create([
            'name'        => $validated['name'],
            'permissions' => $validated['permissions'],
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(AdminRole $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, AdminRole $role)
    {
        $permissionKeys = implode(',', array_keys(AdminRole::ALL_PERMISSIONS));

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100', "unique:admin_roles,name,{$role->id}"],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', "in:{$permissionKeys}"],
        ]);

        $role->update([
            'name'        => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(AdminRole $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that has staff members assigned to it.');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
