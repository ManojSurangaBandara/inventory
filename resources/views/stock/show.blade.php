@extends('layouts.app')

@section('title', 'Stock Movement Details & Audit History')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('stock.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">&larr; Back to Stock Movements</a>
            <h2 class="text-xl font-bold text-white">Stock Movement #{{ $movement->reference_code }}</h2>
        </div>

        <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
            Current State: {{ $movement->current_state }}
        </span>
    </div>

    <!-- Movement Details Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Inventory Product</span>
            <div class="font-bold text-white text-base">{{ $movement->item->name ?? 'N/A' }}</div>
            <div class="text-xs text-indigo-400 font-mono">SKU: {{ $movement->item->sku ?? 'N/A' }}</div>
            <div class="text-xs text-slate-300 font-semibold">Quantity: {{ $movement->quantity }} {{ $movement->item->unit ?? 'pcs' }}</div>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Movement Logistics</span>
            <div class="text-xs text-slate-300 font-semibold">Type: <span class="uppercase text-indigo-400 font-bold">{{ $movement->type }}</span></div>
            <div class="text-xs text-slate-300">Warehouse: <strong class="text-white">{{ $movement->warehouse->name ?? 'N/A' }}</strong></div>
            @if($movement->targetWarehouse)
                <div class="text-xs text-slate-300">Target Warehouse: <strong class="text-emerald-400">{{ $movement->targetWarehouse->name }}</strong></div>
            @endif
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Audit Meta</span>
            <div class="text-xs text-slate-300">Initiated By: <strong class="text-white">{{ $movement->creator->name ?? 'System' }}</strong></div>
            <div class="text-xs text-slate-400">Created: {{ $movement->created_at->format('M d, Y H:i:s') }}</div>
        </div>
    </div>

    <!-- Workflow Audit Log History Timeline -->
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
                            Transition: 
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
                <p class="text-slate-500 text-xs">No workflow transitions logged yet.</p>
            @endempty
        </div>
    </div>
</div>
@endsection
