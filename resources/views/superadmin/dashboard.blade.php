@extends('layouts.app')

@section('title', 'Super Admin Global Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-amber-900/30 via-slate-900 to-indigo-950/40 border border-amber-500/20 rounded-3xl p-6 relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-xl font-bold text-white mb-1">Global System Control Panel</h2>
            <p class="text-xs text-slate-300 max-w-xl">Super Admin overview. You are operating across all organizations to manage platform accounts and administrators.</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Total Organizations</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $stats['total_organizations'] }}</p>
            <span class="text-[11px] text-emerald-400 font-medium">{{ $stats['active_organizations'] }} active accounts</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Platform Users</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $stats['total_users'] }}</p>
            <span class="text-[11px] text-slate-400">Across all organizations</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Total Inventory Items</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $stats['total_inventory_items'] }}</p>
            <span class="text-[11px] text-slate-400">Global items cataloged</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Database Tenancy</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">Single DB</p>
            <span class="text-[11px] text-amber-400 font-medium">Organization Scoped Isolation</span>
        </div>
    </div>

    <!-- Organizations Quick Action Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-white text-sm">Recent Organizations</h3>
                <p class="text-xs text-slate-400">Platform organization registration list</p>
            </div>
            <a href="{{ route('superadmin.organizations') }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-medium transition">
                Manage All Organizations &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Organization Name</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Total Users</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($organizations as $org)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 font-semibold text-white">{{ $org->name }}</td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ $org->code }}</td>
                            <td class="px-4 py-3">{{ $org->users_count }} users</td>
                            <td class="px-4 py-3">
                                @if($org->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/30 font-semibold">Suspended</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-400">{{ $org->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No organizations found.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
