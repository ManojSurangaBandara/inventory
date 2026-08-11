@extends('layouts.app')

@section('title', 'Purchase Order Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('orders.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">&larr; Back to Purchase Orders</a>
            <h2 class="text-xl font-bold text-white">Purchase Order #{{ $order->po_number }}</h2>
        </div>

        <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
            Current State: {{ $order->current_state }}
        </span>
    </div>

    <!-- PO Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Supplier</span>
            <div class="font-bold text-white text-base">{{ $order->supplier->name ?? 'N/A' }}</div>
            <div class="text-xs text-slate-400">{{ $order->supplier->email ?? 'No email' }}</div>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Destination Warehouse</span>
            <div class="font-bold text-white text-base">{{ $order->warehouse->name ?? 'N/A' }}</div>
            <div class="text-xs text-indigo-400 font-mono">{{ $order->warehouse->code ?? '' }}</div>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Total Financial Value</span>
            <div class="font-bold text-emerald-400 text-2xl">${{ number_format($order->total_amount, 2) }}</div>
            <div class="text-xs text-slate-400">Created by {{ $order->creator->name ?? 'System' }}</div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-3">
        <h3 class="font-bold text-white text-base">Order Line Items</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Item Product</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Quantity</th>
                        <th class="px-4 py-3">Unit Price</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($order->items as $pi)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3 font-bold text-white">{{ $pi->item->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-mono text-indigo-400">{{ $pi->item->sku ?? 'N/A' }}</td>
                            <td class="px-4 py-3 font-bold text-white">{{ $pi->quantity }} {{ $pi->item->unit ?? 'pcs' }}</td>
                            <td class="px-4 py-3 text-slate-300">${{ number_format($pi->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-400">${{ number_format($pi->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Audit History Trail -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="font-bold text-white text-base">Workflow Transition Audit Trail</h3>

        <div class="relative pl-6 border-l-2 border-slate-800 space-y-6">
            @forelse($logs as $log)
                <div class="relative group">
                    <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-indigo-600 border-4 border-slate-900"></div>

                    <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-white text-xs">{{ $log->action }}</span>
                            <span class="text-[10px] text-slate-400">{{ $log->created_at->format('M d, Y H:i:s') }}</span>
                        </div>

                        <div class="text-xs text-slate-300">
                            State Change: 
                            <span class="font-mono text-slate-400">{{ $log->from_state ?? 'Draft' }}</span> 
                            &rarr; 
                            <span class="font-mono text-indigo-400 font-bold">{{ $log->to_state }}</span>
                        </div>

                        <div class="text-[11px] text-slate-400">
                            Executed by: <strong class="text-slate-200">{{ $log->user->name ?? 'System' }}</strong>
                        </div>

                        @if($log->notes)
                            <div class="mt-2 text-xs italic bg-slate-900 p-2.5 rounded-xl border border-slate-800 text-slate-300">
                                "{{ $log->notes }}"
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-slate-500 text-xs">No workflow history logged yet.</p>
            @endempty
        </div>
    </div>
</div>
@endsection
