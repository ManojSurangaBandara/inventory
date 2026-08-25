@extends('layouts.app')

@section('title', 'Current Stock Balance')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Current Stock Balance</h2>
            <p class="text-xs text-slate-400">Live physical stock levels, item valuations, and safety thresholds across all catalog items.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('stock.index') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Stock Requisition</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Units on Hand</p>
                <h3 class="text-2xl font-bold text-white mt-1">{{ number_format($totalUnits) }}</h3>
                <span class="text-[10px] text-slate-500 mt-0.5 block">Physical inventory count</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Stock Value</p>
                <h3 class="text-2xl font-bold text-white mt-1">Rs. {{ number_format($totalValuation, 2) }}</h3>
                <span class="text-[10px] text-slate-500 mt-0.5 block">Standard cost valuation</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">In-Stock SKUs</p>
                <h3 class="text-2xl font-bold text-white mt-1">{{ $inStockCount }}</h3>
                <span class="text-[10px] text-emerald-400 mt-0.5 block">Active stocked items</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-3xl bg-slate-900/80 border border-slate-800 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Low / Depleted Alerts</p>
                <h3 class="text-2xl font-bold {{ ($lowStockCount + $outOfStockCount) > 0 ? 'text-rose-400' : 'text-slate-400' }} mt-1">{{ $lowStockCount + $outOfStockCount }}</h3>
                <span class="text-[10px] text-slate-500 mt-0.5 block">{{ $lowStockCount }} Low, {{ $outOfStockCount }} Zero Stock</span>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ ($lowStockCount + $outOfStockCount) > 0 ? 'bg-rose-500/10 border-rose-500/20 text-rose-400' : 'bg-slate-800 text-slate-500' }} border flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl">
        <form method="GET" action="{{ route('stock.balance') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="relative lg:col-span-2">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by SKU, item name, or description..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <!-- Category Filter -->
            <div>
                <select name="category_id" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Stock Status Filter -->
            <div>
                <select name="stock_status" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="">All Stock Statuses</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock (> 0)</option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock (<= Min)</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Zero Stock (0)</option>
                </select>
            </div>

            <!-- Sorting & Action -->
            <div class="flex items-center space-x-2">
                <select name="sort" onchange="this.form.submit()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Highest Stock</option>
                    <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Lowest Stock</option>
                    <option value="valuation_desc" {{ request('sort') === 'valuation_desc' ? 'selected' : '' }}>Highest Valuation</option>
                    <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Item Name (A-Z)</option>
                    <option value="sku_asc" {{ request('sort') === 'sku_asc' ? 'selected' : '' }}>SKU (A-Z)</option>
                </select>
                @if(request()->hasAny(['search', 'category_id', 'stock_status', 'sort']))
                    <a href="{{ route('stock.balance') }}" title="Reset Filters" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Stock Balance Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">SKU & Item Name</th>
                        <th class="px-4 py-3.5">Category Hierarchy</th>
                        <th class="px-4 py-3.5">Unit Cost</th>
                        <th class="px-4 py-3.5 text-right">Current Stock Balance</th>
                        <th class="px-4 py-3.5 text-right">Reorder Threshold</th>
                        <th class="px-4 py-3.5 text-right">Total Valuation</th>
                        <th class="px-4 py-3.5 text-center">Health Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($items as $item)
                        @php
                            $isDepleted = $item->current_stock <= 0;
                            $isLow = $item->current_stock <= $item->reorder_level && !$isDepleted;
                            $valuation = $item->current_stock * $item->unit_cost;
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <!-- SKU & Name -->
                            <td class="px-4 py-4">
                                <div class="font-bold text-white text-sm">{{ $item->name }}</div>
                                <div class="font-mono text-indigo-400 text-[11px]">{{ $item->sku }}</div>
                                @if($item->description)
                                    <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">{{ $item->description }}</div>
                                @endif
                            </td>

                            <!-- Categories Trail -->
                            <td class="px-4 py-4">
                                <div class="text-xs font-semibold text-slate-200">
                                    {{ $item->category1->name ?? 'Uncategorized' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono flex items-center space-x-1 mt-0.5">
                                    @if($item->category2)
                                        <span>&rarr; {{ $item->category2->name }}</span>
                                    @endif
                                    @if($item->category3)
                                        <span>&rarr; {{ $item->category3->name }}</span>
                                    @endif
                                    @if($item->category4)
                                        <span>&rarr; {{ $item->category4->name }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Unit Cost -->
                            <td class="px-4 py-4">
                                <div class="font-bold text-white">Rs. {{ number_format($item->unit_cost, 2) }}</div>
                                <div class="text-[10px] text-slate-400">per {{ $item->unit }}</div>
                            </td>

                            <!-- Current Stock Balance -->
                            <td class="px-4 py-4 text-right font-mono">
                                <div class="text-base font-extrabold {{ $isDepleted ? 'text-rose-400' : ($isLow ? 'text-amber-400' : 'text-emerald-400') }}">
                                    {{ number_format($item->current_stock) }} <span class="text-xs font-normal text-slate-400">{{ $item->unit }}</span>
                                </div>
                            </td>

                            <!-- Reorder Threshold -->
                            <td class="px-4 py-4 text-right font-mono">
                                <div class="text-xs text-slate-300 font-semibold">{{ number_format($item->reorder_level) }} {{ $item->unit }}</div>
                                <div class="text-[10px] text-slate-500">Min Safety Level</div>
                            </td>

                            <!-- Total Valuation -->
                            <td class="px-4 py-4 text-right font-mono">
                                <div class="font-bold text-white text-xs">Rs. {{ number_format($valuation, 2) }}</div>
                                <div class="text-[10px] text-slate-500">{{ number_format($item->current_stock) }} &times; Rs. {{ number_format($item->unit_cost, 2) }}</div>
                            </td>

                            <!-- Health Status Badge -->
                            <td class="px-4 py-4 text-center">
                                @if($isDepleted)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mr-1.5"></span>
                                        Out of Stock
                                    </span>
                                @elseif($isLow)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5 animate-pulse"></span>
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span>
                                        Adequate
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                <svg class="w-8 h-8 text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span>No items found matching the selected stock balance criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
