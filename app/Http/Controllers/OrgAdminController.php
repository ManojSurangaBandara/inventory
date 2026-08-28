<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrgAdminController extends Controller
{
    /**
     * User Management
     */
    public function users()
    {
        $users = User::where('organization_id', Auth::user()->organization_id)
            ->where('is_super_admin', false)
            ->with(['roles', 'warehouse'])
            ->latest()
            ->get();

        $roles = Role::where('organization_id', Auth::user()->organization_id)->get();
        $warehouses = Warehouse::where('organization_id', Auth::user()->organization_id)->orderBy('name')->get();

        return view('orgadmin.users', compact('users', 'roles', 'warehouses'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'warehouse_id' => 'nullable',
            'is_org_admin' => 'nullable|boolean',
        ]);

        $user = User::create([
            'organization_id' => Auth::user()->organization_id,
            'warehouse_id' => $request->filled('warehouse_id') ? (int) $request->warehouse_id : null,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_super_admin' => false,
            'is_org_admin' => $request->boolean('is_org_admin'),
            'status' => 'active',
        ]);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return redirect()->route('orgadmin.users')->with('success', "User '{$user->name}' created successfully.");
    }

    public function updateUser(Request $request, int $id)
    {
        $user = User::where('organization_id', Auth::user()->organization_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'status' => 'required|in:active,inactive',
            'roles' => 'nullable|array',
            'warehouse_id' => 'nullable',
            'is_org_admin' => 'nullable|boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->status = $request->status;
        $user->is_org_admin = $request->boolean('is_org_admin');
        $user->warehouse_id = $request->filled('warehouse_id') ? (int) $request->warehouse_id : null;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return redirect()->route('orgadmin.users')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Role & Permission Management
     */
    public function roles()
    {
        $roles = Role::where('organization_id', Auth::user()->organization_id)
            ->with(['permissions', 'users'])
            ->get();

        $permissions = Permission::all()->groupBy('module');

        return view('orgadmin.roles', compact('roles', 'permissions'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = Str::slug($request->name);
        
        $role = Role::create([
            'organization_id' => Auth::user()->organization_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'is_system' => false,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('orgadmin.roles')->with('success', "Role '{$role->name}' created successfully.");
    }

    public function updateRole(Request $request, int $id)
    {
        $role = Role::where('organization_id', Auth::user()->organization_id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role->name = $request->name;
        $role->description = $request->description;
        $role->save();

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('orgadmin.roles')->with('success', "Role '{$role->name}' updated.");
    }
}
