@extends('layouts.app')

@section('title', 'Roles & Permissions Builder')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Custom Roles & Permissions</h2>
            <p class="text-xs text-slate-400">Define tenant-specific roles and configure access rights across inventory modules.</p>
        </div>
        <button onclick="document.getElementById('addRoleModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create Custom Role</span>
        </button>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($roles as $role)
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-bold text-white text-base">{{ $role->name }}</h3>
                            <span class="font-mono text-[10px] text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">slug: {{ $role->slug }}</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] bg-slate-800 text-slate-300 border border-slate-700 font-semibold">
                            {{ $role->users->count() }} {{ Str::plural('User', $role->users->count()) }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 min-h-[36px]">{{ $role->description ?? 'No description provided for this role.' }}</p>

                    <div class="pt-2 border-t border-slate-800/80">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-2">Granted Permissions ({{ $role->permissions->count() }})</span>
                        <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto">
                            @forelse($role->permissions as $p)
                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-950 text-slate-300 border border-slate-800">
                                    {{ $p->name }}
                                </span>
                            @empty
                                <span class="text-rose-400 text-[10px]">No permissions assigned yet</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-end">
                    <button onclick="editRole({{ json_encode($role) }}, {{ json_encode($role->permissions->pluck('id')) }})" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition">
                        Configure Permissions &rarr;
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                No custom roles defined yet. Create roles to grant specific user capabilities and workflow transition permissions.
            </div>
        @endempty
    </div>
</div>

<!-- Modal: Create Role -->
<div id="addRoleModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Create Custom Tenant Role</h3>
            <button onclick="document.getElementById('addRoleModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('orgadmin.roles.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Role Title *</label>
                <input type="text" name="name" required placeholder="e.g. Quality Inspector" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Responsibilities and scope for this role..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-indigo-400 uppercase tracking-wider mb-2">Assign Permissions by Module</label>
                <div class="space-y-4">
                    @foreach($permissions as $module => $modulePerms)
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3">
                            <h4 class="text-xs font-bold text-white capitalize mb-2 border-b border-slate-800 pb-1 flex items-center justify-between">
                                <span>{{ $module }} Module</span>
                                <span class="text-[10px] text-slate-500 font-normal">{{ $modulePerms->count() }} permissions</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($modulePerms as $perm)
                                    <label class="flex items-center space-x-2 text-xs text-slate-300 hover:bg-slate-900 p-1.5 rounded cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                        <span class="font-medium text-white">{{ $perm->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addRoleModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Role -->
<div id="editRoleModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Role & Permissions</h3>
            <button onclick="document.getElementById('editRoleModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editRoleForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Role Title *</label>
                <input type="text" name="name" id="edit_role_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" id="edit_role_description" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-indigo-400 uppercase tracking-wider mb-2">Permissions</label>
                <div class="space-y-4">
                    @foreach($permissions as $module => $modulePerms)
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-3">
                            <h4 class="text-xs font-bold text-white capitalize mb-2 border-b border-slate-800 pb-1">{{ $module }} Module</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($modulePerms as $perm)
                                    <label class="flex items-center space-x-2 text-xs text-slate-300 hover:bg-slate-900 p-1.5 rounded cursor-pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="edit_perm_{{ $perm->id }}" class="edit-perm-checkbox rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                        <span class="font-medium text-white">{{ $perm->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editRoleModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Update Role</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editRole(role, permIds) {
        document.getElementById('editRoleForm').action = "{{ url('/admin/roles') }}/" + role.id;
        document.getElementById('edit_role_name').value = role.name;
        document.getElementById('edit_role_description').value = role.description || '';

        document.querySelectorAll('.edit-perm-checkbox').forEach(cb => cb.checked = false);

        permIds.forEach(id => {
            const cb = document.getElementById('edit_perm_' + id);
            if (cb) cb.checked = true;
        });

        document.getElementById('editRoleModal').classList.remove('hidden');
    }
</script>
@endsection
