@extends('layouts.app')

@section('title', 'Tenant Dashboard Overview')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-indigo-500/20 rounded-3xl p-6 shadow-xl relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative z-10">
            <div>
                <h2 class="text-xl font-bold text-white mb-1">Welcome back, {{ Auth::user()->name }}</h2>
                <p class="text-xs text-slate-300">Tenant: <strong class="text-indigo-400">{{ Auth::user()->organization->name ?? 'Default Org' }}</strong> • Single DB Scoped Operations</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('inventory.items') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30 transition">
                    + Add New Item
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Total Catalog Items</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $totalItems }}</p>
            <span class="text-[11px] text-slate-400">Active SKUs</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Low Stock Alerts</span>
                <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-rose-400">{{ $lowStockItems->count() }}</p>
            <span class="text-[11px] text-rose-300 font-medium">Reorder threshold reached</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Stock Movements</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $totalMovements }}</p>
            <span class="text-[11px] text-emerald-400">Workflow Controlled</span>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Purchase Orders</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $totalOrders }}</p>
            <span class="text-[11px] text-purple-400 font-medium">{{ $workflowsCount }} Workflows Active</span>
        </div>
    </div>

    <!-- Low Stock Alert Banner / Table -->
    @if($lowStockItems->count() > 0)
        <div class="bg-rose-950/20 border border-rose-500/30 rounded-3xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    <h3 class="font-bold text-rose-300 text-sm">Low Stock Items Reorder Alert</h3>
                </div>
                <a href="{{ route('inventory.items', ['low_stock' => 1]) }}" class="text-xs text-rose-400 hover:underline">View All &rarr;</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($lowStockItems->take(6) as $item)
                    <div class="bg-slate-900 border border-rose-500/20 rounded-2xl p-3.5 flex items-center justify-between">
                        <div>
                            <span class="font-mono text-[10px] text-slate-400 block">{{ $item->sku }}</span>
                            <span class="font-bold text-white text-xs block truncate">{{ $item->name }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-rose-400 block">{{ $item->current_stock }} / {{ $item->reorder_level }} {{ $item->unit }}</span>
                            <span class="text-[9px] text-slate-500 uppercase">Current Stock</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent Stock Movements Audit Section -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-white text-sm">Recent Stock Movements & Workflow Status</h3>
                <p class="text-xs text-slate-400">Live operational lifecycle feed</p>
            </div>
            <a href="{{ route('stock.index') }}" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-medium transition">
                View All Movements &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Reference Code</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Warehouse</th>
                        <th class="px-4 py-3">Quantity</th>
                        <th class="px-4 py-3">Current State</th>
                        <th class="px-4 py-3 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentMovements as $m)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 font-mono font-bold text-indigo-400">
                                <a href="{{ route('stock.show', $m->id) }}" class="hover:underline">{{ $m->reference_code }}</a>
                            </td>
                            <td class="px-4 py-3 uppercase font-semibold text-[10px] text-slate-400">{{ $m->type }}</td>
                            <td class="px-4 py-3 font-medium text-white">{{ $m->item->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $m->warehouse->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-bold text-white">{{ $m->quantity }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] bg-slate-800 text-indigo-300 border border-indigo-500/30 font-semibold uppercase">
                                    {{ $m->current_state }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-400">{{ $m->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">No recent stock movements found.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
