<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('status', 'active')->count(),
            'total_users' => User::where('is_super_admin', false)->count(),
            'total_inventory_items' => InventoryItem::withoutGlobalScopes()->count(),
        ];

        $organizations = Organization::withCount('users')->latest()->take(5)->get();

        return view('superadmin.dashboard', compact('stats', 'organizations'));
    }

    public function organizations()
    {
        $organizations = Organization::with(['users' => function($q) {
            $q->where('is_org_admin', true);
        }])->withCount('users')->latest()->get();

        return view('superadmin.organizations', compact('organizations'));
    }

    public function storeOrganization(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:organizations,code',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $org = Organization::create([
            'name' => $request->name,
            'code' => Str::slug($request->code),
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => 'active',
        ]);

        User::create([
            'organization_id' => $org->id,
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'is_super_admin' => false,
            'is_org_admin' => true,
            'status' => 'active',
        ]);

        return redirect()->route('superadmin.organizations')->with('success', "Organization '{$org->name}' and its Admin created successfully!");
    }

    public function createOrgAdmin(Request $request, int $orgId)
    {
        $org = Organization::findOrFail($orgId);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'organization_id' => $org->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_super_admin' => false,
            'is_org_admin' => true,
            'status' => 'active',
        ]);

        return redirect()->route('superadmin.organizations')->with('success', "Organization Admin added for '{$org->name}'.");
    }

    public function toggleStatus(int $orgId)
    {
        $org = Organization::findOrFail($orgId);
        $org->status = $org->status === 'active' ? 'suspended' : 'active';
        $org->save();

        return redirect()->back()->with('success', "Organization '{$org->name}' status updated to {$org->status}.");
    }
}
