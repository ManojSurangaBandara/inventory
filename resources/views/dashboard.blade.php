@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
<div class="space-y-6">
    @php
        $chartPayload = $allItems->take(12)->map(function($item) {
            return [
                'sku' => $item->sku,
                'name' => $item->name,
                'current_stock' => (int) $item->current_stock,
                'reorder_level' => (int) $item->reorder_level,
                'unit' => $item->unit,
                'is_low' => $item->current_stock <= $item->reorder_level && $item->current_stock > 0,
                'is_out' => $item->current_stock <= 0,
            ];
        })->values();
    @endphp

    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-indigo-500/20 rounded-3xl p-6 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white mb-1">Welcome back, {{ Auth::user()->name }}</h2>
            <p class="text-xs text-slate-300">Organization: <strong class="text-indigo-400">{{ Auth::user()->organization->name ?? 'Default Org' }}</strong></p>
        </div>
        <div class="flex items-center space-x-2.5">
            <a href="{{ route('stock.transfers') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-semibold shadow transition flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Stock Transfer</span>
            </a>

            <a href="{{ route('stock.index') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/30 rounded-xl text-xs font-semibold shadow transition flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Stock Request</span>
            </a>

            <a href="{{ route('inventory.items') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition flex items-center space-x-1.5">
                <span>+ Add Item</span>
            </a>
        </div>
    </div>

    <!-- 4 Key Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Items -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Total Items</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $totalItems }}</p>
            <span class="text-[11px] text-slate-400">{{ number_format($totalStockUnits) }} units on hand</span>
        </div>

        <!-- Total Valuation -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Total Valuation</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-emerald-400">${{ number_format($totalStockValuation, 2) }}</p>
            <span class="text-[11px] text-emerald-400/80">Asset inventory value</span>
        </div>

        <!-- Low Stock Alerts -->
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

        <!-- Stock Movements -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-slate-400">Stock Movements</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </span>
            </div>
            <p class="text-2xl font-bold text-white">{{ $totalMovements }}</p>
            <span class="text-[11px] text-purple-400 font-medium">{{ $workflowsCount }} Workflows Active</span>
        </div>
    </div>

    <!-- Stock Levels & Health Visual Analytics (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stock Levels Chart (2 Cols) -->
        <div class="lg:col-span-2 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="font-bold text-white text-base">Stock Amounts</h3>
                    <p class="text-xs text-slate-400">On-hand stock units compared with reorder safety levels</p>
                </div>
                <a href="{{ route('inventory.items') }}" class="text-xs text-indigo-400 hover:underline">Manage Items &rarr;</a>
            </div>

            <!-- Chart Canvas -->
            <div class="h-64 w-full relative">
                <canvas id="stockAnalyticsBarChart"></canvas>
            </div>
        </div>

        <!-- Stock Health Distribution Donut (1 Col) -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4">
            <div class="border-b border-slate-800 pb-3">
                <h3 class="font-bold text-white text-base">Stock Health</h3>
                <p class="text-xs text-slate-400">Catalog inventory status proportion</p>
            </div>

            <div class="h-48 relative flex items-center justify-center">
                <canvas id="stockHealthDonutChart"></canvas>
            </div>

            <!-- Health Summary Pills -->
            <div class="grid grid-cols-3 gap-2 text-center text-xs pt-2 border-t border-slate-800">
                <div class="bg-slate-950 p-2 rounded-xl border border-emerald-500/20">
                    <span class="text-emerald-400 font-bold text-sm block">{{ $healthyStockCount }}</span>
                    <span class="text-[10px] text-slate-400">Adequate</span>
                </div>
                <div class="bg-slate-950 p-2 rounded-xl border border-amber-500/20">
                    <span class="text-amber-400 font-bold text-sm block">{{ $lowStockCount }}</span>
                    <span class="text-[10px] text-slate-400">Low Stock</span>
                </div>
                <div class="bg-slate-950 p-2 rounded-xl border border-rose-500/20">
                    <span class="text-rose-400 font-bold text-sm block">{{ $outOfStockCount }}</span>
                    <span class="text-[10px] text-slate-400">Depleted</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert Banner (Conditional) -->
    @if($lowStockItems->count() > 0)
        <div class="bg-rose-950/20 border border-rose-500/30 rounded-3xl p-6 space-y-4">
            <div class="flex items-center justify-between">
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
                            <span class="font-bold text-white text-xs block truncate max-w-[160px]">{{ $item->name }}</span>
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

    <!-- Recent Stock Movements Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-white text-sm">Recent Stock Movements</h3>
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
                        <th class="px-4 py-3">Item / Description</th>
                        <th class="px-4 py-3">Warehouse</th>
                        <th class="px-4 py-3">Quantity</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($recentMovements as $m)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 font-mono font-bold text-indigo-400">
                                <a href="{{ route('stock.show', $m->id) }}" class="hover:underline">{{ $m->reference_code }}</a>
                            </td>
                            <td class="px-4 py-3 uppercase font-semibold text-[10px] text-slate-400">
                                @if($m->type === 'transfer')
                                    <span class="text-indigo-400">Transfer</span>
                                @elseif($m->type === 'inbound')
                                    <span class="text-emerald-400">Inbound</span>
                                @else
                                    <span>{{ $m->type }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-white">
                                @if($m->items->count() > 1)
                                    {{ $m->items->count() }} Line Items
                                @else
                                    {{ $m->items->first()->item->name ?? ($m->item->name ?? 'N/A') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                @if($m->type === 'transfer' && $m->targetWarehouse)
                                    {{ $m->warehouse->name ?? 'N/A' }} &rarr; {{ $m->targetWarehouse->name }}
                                @else
                                    {{ $m->warehouse->name ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold text-white">
                                {{ $m->total_quantity ?: $m->quantity }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] bg-slate-800 text-indigo-300 border border-indigo-500/30 font-semibold uppercase">
                                    {{ $m->current_state }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-400">{{ $m->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">No recent stock movements recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
    const chartDataItems = @json($chartPayload);

    let barChartInstance = null;
    let donutChartInstance = null;

    function getThemeColors() {
        const isDark = document.documentElement.classList.contains('dark') || document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            textColor: isDark ? '#94a3b8' : '#475569',
            gridColor: isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)',
            tooltipBg: isDark ? '#0f172a' : '#ffffff',
            tooltipText: isDark ? '#f8fafc' : '#0f172a',
        };
    }

    function initBarChart() {
        const ctx = document.getElementById('stockAnalyticsBarChart');
        if (!ctx) return;

        const theme = getThemeColors();
        const labels = chartDataItems.map(i => i.sku);

        const datasets = [
            {
                label: 'On-Hand Stock',
                data: chartDataItems.map(i => i.current_stock),
                backgroundColor: chartDataItems.map(i => i.is_out ? 'rgba(239, 68, 68, 0.85)' : (i.is_low ? 'rgba(245, 158, 11, 0.85)' : 'rgba(99, 102, 241, 0.85)')),
                borderRadius: 6,
            },
            {
                label: 'Reorder Level',
                data: chartDataItems.map(i => i.reorder_level),
                backgroundColor: 'rgba(148, 163, 184, 0.35)',
                borderColor: 'rgba(148, 163, 184, 0.7)',
                borderWidth: 1.5,
                borderDash: [4, 4],
                borderRadius: 6,
            }
        ];

        if (barChartInstance) {
            barChartInstance.destroy();
        }

        barChartInstance = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: theme.textColor, font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' } }
                    },
                    tooltip: {
                        backgroundColor: theme.tooltipBg,
                        titleColor: theme.tooltipText,
                        bodyColor: theme.tooltipText,
                        borderColor: 'rgba(99, 102, 241, 0.3)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            afterLabel: function(context) {
                                const item = chartDataItems[context.dataIndex];
                                return `Item: ${item.name} (${item.unit})`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: theme.textColor, font: { family: 'Plus Jakarta Sans', size: 10, weight: '600' } }
                    },
                    y: {
                        grid: { color: theme.gridColor },
                        ticks: { color: theme.textColor, font: { family: 'Plus Jakarta Sans', size: 10 } }
                    }
                }
            }
        });
    }

    function initDonutChart() {
        const ctx = document.getElementById('stockHealthDonutChart');
        if (!ctx) return;

        const healthy = chartDataItems.filter(i => !i.is_low && !i.is_out).length;
        const low = chartDataItems.filter(i => i.is_low).length;
        const out = chartDataItems.filter(i => i.is_out).length;

        if (donutChartInstance) {
            donutChartInstance.destroy();
        }

        donutChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Adequate Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [healthy, low, out],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(239, 68, 68, 0.85)'
                    ],
                    borderColor: 'transparent',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initBarChart();
        initDonutChart();

        window.addEventListener('themeChanged', function() {
            setTimeout(() => {
                initBarChart();
                initDonutChart();
            }, 100);
        });
    });
</script>
@endsection
