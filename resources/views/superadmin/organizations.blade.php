@extends('layouts.app')

@section('title', 'Organizations Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Organizations & Organization Admins</h2>
            <p class="text-xs text-slate-400">Create new client organizations and assign Organization Admins.</p>
        </div>
        <button onclick="document.getElementById('addOrgModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create Organization & Admin</span>
        </button>
    </div>

    <!-- Organizations Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">Organization</th>
                        <th class="px-4 py-3.5">Code</th>
                        <th class="px-4 py-3.5">Contact Details</th>
                        <th class="px-4 py-3.5">Org Admins</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($organizations as $org)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4">
                                <div class="font-bold text-white text-sm">{{ $org->name }}</div>
                                <span class="text-[10px] text-slate-400">ID: #{{ $org->id }} • {{ $org->users_count }} Total Users</span>
                            </td>
                            <td class="px-4 py-4 font-mono text-indigo-400 bg-indigo-500/5 rounded-lg border border-indigo-500/20 px-2 py-1 inline-block mt-2">{{ $org->code }}</td>
                            <td class="px-4 py-4">
                                <div class="text-slate-200">{{ $org->email ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $org->phone ?? 'No phone' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                @forelse($org->users as $admin)
                                    <div class="flex items-center space-x-2 my-1">
                                        <div class="w-6 h-6 rounded-full bg-indigo-600/30 text-indigo-300 flex items-center justify-center font-bold text-[10px]">
                                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-200">{{ $admin->name }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $admin->email }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-rose-400 text-[10px]">No Org Admin Assigned</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-4">
                                @if($org->status === 'active')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold">Active</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/30 font-semibold">Suspended</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right space-x-2">
                                <form action="{{ route('superadmin.organizations.toggle', $org->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-xl border text-[11px] font-medium transition {{ $org->status === 'active' ? 'border-rose-500/30 text-rose-400 hover:bg-rose-500/10' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10' }}">
                                        {{ $org->status === 'active' ? 'Suspend Org' : 'Activate Org' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No organizations found. Click create to get started.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create Organization & Admin -->
<div id="addOrgModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-xl shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Create New Organization & Org Admin</h3>
            <button onclick="document.getElementById('addOrgModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('superadmin.organizations.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">1. Organization Details</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Organization Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Apex Logistics" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Code / Slug *</label>
                        <input type="text" name="code" required placeholder="e.g. apex" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Org Email</label>
                        <input type="email" name="email" placeholder="contact@apex.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Phone</label>
                        <input type="text" name="phone" placeholder="+1 555 0192" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>
            </div>

            <div class="space-y-3 pt-3 border-t border-slate-800">
                <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">2. Organization Admin Credentials</h4>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Admin Full Name *</label>
                    <input type="text" name="admin_name" required placeholder="John Doe" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Admin Email *</label>
                        <input type="email" name="admin_email" required placeholder="admin@apex.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Password *</label>
                        <input type="password" name="admin_password" required placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addOrgModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Create Organization</button>
            </div>
        </form>
    </div>
</div>
@endsection
