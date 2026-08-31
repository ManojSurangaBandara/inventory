@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Users & Facility Access</h2>
            <p class="text-xs text-slate-400">Manage user accounts, assign warehouse locations (Subject Clerks / Storemen), and configure roles for {{ Auth::user()->organization->name ?? 'your organization' }}.</p>
        </div>
        <button onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Create New User</span>
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">User</th>
                        <th class="px-4 py-3.5">Email</th>
                        <th class="px-4 py-3.5">Assigned Facility / Warehouse</th>
                        <th class="px-4 py-3.5">Assigned Roles</th>
                        <th class="px-4 py-3.5">Admin Level</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white">{{ $u->name }}</div>
                                        <div class="text-[10px] text-slate-400">Created {{ $u->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-300">{{ $u->email }}</td>
                            
                            <!-- Assigned Facility / Warehouse -->
                            <td class="px-4 py-4">
                                @if($u->warehouse)
                                    <div class="space-y-0.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $u->warehouse->type_badge_class }}">
                                            {{ $u->warehouse->type_label }}
                                        </span>
                                        <div class="font-semibold text-white text-xs mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>{{ $u->warehouse->name }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-400 border border-slate-700 font-medium flex items-center gap-1 w-fit">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>All Facilities (Global)</span>
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($u->roles as $r)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 border border-slate-700 font-medium">
                                            {{ $r->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-500 text-[10px]">No Custom Role</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if($u->is_org_admin)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] bg-amber-500/10 text-amber-300 border border-amber-500/30 font-semibold">Org Admin</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] bg-slate-800 text-slate-400 border border-slate-700">Standard User</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($u->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/30 font-semibold">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button onclick="editUser({{ json_encode($u) }}, {{ json_encode($u->roles->pluck('id')) }})" class="px-3.5 py-1.5 rounded-xl border border-slate-700 text-[11px] font-semibold text-slate-300 hover:bg-slate-800 transition shadow-sm">
                                    Edit Roles & Facility
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No organization users created yet.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create User -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Create Organization User</h3>
            <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('orgadmin.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. Kamal Perera (Subject Clerk)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address *</label>
                <input type="email" name="email" required placeholder="clerk@apexlogistics.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Password *</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <!-- Assigned Warehouse Location -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Assigned Facility / Warehouse Location</label>
                <select name="warehouse_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-medium">
                    <option value="">-- All Facilities / Global Headquarters --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">[{{ $wh->type_label }}] {{ $wh->name }} ({{ $wh->code }})</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Assign to a specific warehouse to scope stock balance and requests to this depot.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Assign Organization Roles</label>
                <div class="space-y-2 max-h-40 overflow-y-auto p-2 bg-slate-950 border border-slate-800 rounded-xl">
                    @forelse($roles as $role)
                        <label class="flex items-center space-x-2 text-xs text-slate-300 hover:bg-slate-900 p-1 rounded cursor-pointer">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-semibold text-white block">{{ $role->name }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $role->description ?? 'No description' }}</span>
                            </div>
                        </label>
                    @empty
                        <p class="text-slate-500 text-[11px] p-2">No roles configured. Go to Roles & Permissions to create custom roles.</p>
                    @endempty
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit User -->
<div id="editUserModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit User & Facility Access</h3>
            <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editUserForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name *</label>
                <input type="text" name="name" id="edit_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address *</label>
                <input type="email" name="email" id="edit_email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">New Password (leave empty to keep current)</label>
                <input type="password" name="password" placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <!-- Assigned Warehouse Location -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Assigned Facility / Warehouse Location</label>
                <select name="warehouse_id" id="edit_warehouse_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-medium">
                    <option value="">-- All Facilities / Global Headquarters --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">[{{ $wh->type_label }}] {{ $wh->name }} ({{ $wh->code }})</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Assign to a specific warehouse to scope stock balance and requests to this depot.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Account Status</label>
                <select name="status" id="edit_status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" name="is_org_admin" id="edit_is_org_admin" value="1" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                <label for="edit_is_org_admin" class="text-xs text-slate-300">Grant Org Admin Privileges</label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Assigned Organization Roles</label>
                <div class="space-y-2 max-h-40 overflow-y-auto p-2 bg-slate-950 border border-slate-800 rounded-xl">
                    @foreach($roles as $role)
                        <label class="flex items-center space-x-2 text-xs text-slate-300 hover:bg-slate-900 p-1 rounded cursor-pointer">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" id="edit_role_{{ $role->id }}" class="edit-role-checkbox rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-semibold text-white">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editUser(user, roleIds) {
        document.getElementById('editUserForm').action = "{{ url('/admin/users') }}/" + user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_status').value = user.status;
        document.getElementById('edit_warehouse_id').value = (user.warehouse_id !== null && user.warehouse_id !== undefined) ? user.warehouse_id.toString() : '';
        document.getElementById('edit_is_org_admin').checked = user.is_org_admin == 1;

        // Uncheck all roles first
        document.querySelectorAll('.edit-role-checkbox').forEach(cb => cb.checked = false);

        // Check assigned roles
        roleIds.forEach(id => {
            const cb = document.getElementById('edit_role_' + id);
            if (cb) cb.checked = true;
        });

        document.getElementById('editUserModal').classList.remove('hidden');
    }
</script>
@endsection
