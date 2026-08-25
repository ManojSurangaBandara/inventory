@extends('layouts.app')

@section('title', 'Workflow Builder')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Configured Workflows</h2>
            <p class="text-xs text-slate-400">Design lifecycle states, physical stage locations, and role approval rules for inventory processes.</p>
        </div>
        <button onclick="document.getElementById('addWorkflowModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create New Workflow</span>
        </button>
    </div>

    <!-- Workflows Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($workflows as $wf)
            @php
                $moduleMeta = [
                    'StockDispatch' => ['label' => 'Outbound Dispatch', 'color' => 'bg-amber-500/10 text-amber-400 border-amber-500/30'],
                    'StockReceipt' => ['label' => 'Inbound Receipt', 'color' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'],
                    'StockTransfer' => ['label' => 'Stock Transfer', 'color' => 'bg-blue-500/10 text-blue-400 border-blue-500/30'],
                    'StockAdjustment' => ['label' => 'Status & Adjustment', 'color' => 'bg-purple-500/10 text-purple-400 border-purple-500/30'],
                    'StockMovement' => ['label' => 'General Movement', 'color' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30'],
                    'PurchaseOrder' => ['label' => 'Purchase Order', 'color' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30'],
                    'InventoryItem' => ['label' => 'Item Master', 'color' => 'bg-slate-500/10 text-slate-400 border-slate-500/30'],
                ][$wf->entity_type] ?? ['label' => $wf->entity_type, 'color' => 'bg-slate-500/10 text-slate-400 border-slate-500/30'];
            @endphp
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4 hover:border-indigo-500/30 transition {{ !$wf->is_active ? 'opacity-70' : '' }}">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] {{ $moduleMeta['color'] }} border font-bold uppercase tracking-wider">
                                {{ $moduleMeta['label'] }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <form action="{{ route('workflows.toggle-active', $wf->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" title="Click to toggle (Currently {{ $wf->is_active ? 'Active' : 'Inactive' }})" class="flex items-center space-x-1 text-[10px] {{ $wf->is_active ? 'text-emerald-400 hover:text-emerald-300' : 'text-slate-500 hover:text-slate-400' }} font-semibold px-2 py-0.5 rounded-full {{ $wf->is_active ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-slate-800 border border-slate-700' }} transition">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $wf->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                    <span>{{ $wf->is_active ? 'Active' : 'Inactive' }}</span>
                                </button>
                            </form>
                            <form action="{{ route('workflows.destroy', $wf->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete workflow \'{{ $wf->name }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete Workflow" class="text-slate-500 hover:text-rose-400 p-1 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Location Scope Tag -->
                    <div>
                        @if($wf->warehouse)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 font-semibold gap-1">
                                <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Facility: <strong>{{ $wf->warehouse->name }}</strong> ({{ $wf->warehouse->code }})</span>
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] bg-slate-800/80 text-slate-400 border border-slate-700/60 font-semibold gap-1">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Scope: All Locations (Global)</span>
                            </span>
                        @endif
                    </div>

                    <div>
                        <h3 class="font-bold text-white text-base">{{ $wf->name }}</h3>
                        <p class="text-xs text-slate-400 mt-1 line-clamp-2">{{ $wf->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="flex items-center space-x-4 pt-2 border-t border-slate-800 text-xs text-slate-400">
                        <div>
                            <span class="font-bold text-white text-sm block">{{ $wf->states_count }}</span>
                            <span class="text-[10px] text-slate-500">States</span>
                        </div>
                        <div class="w-px h-6 bg-slate-800"></div>
                        <div>
                            <span class="font-bold text-white text-sm block">{{ $wf->transitions_count }}</span>
                            <span class="text-[10px] text-slate-500">Transitions</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('workflows.builder', $wf->id) }}" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold text-center transition shadow-md shadow-indigo-600/20 block">
                        Open Visual Builder &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-800/80 mx-auto flex items-center justify-center text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="text-white font-bold text-sm">No Workflows Defined</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">Create custom workflows to control approval steps and status changes for Dispatches, Receipts, Transfers, and Purchase Orders.</p>
            </div>
        @endempty
    </div>
</div>

<!-- Modal: Create Workflow Definition -->
<div id="addWorkflowModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">New Custom Workflow</h3>
            <button onclick="document.getElementById('addWorkflowModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('workflows.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Workflow Title *</label>
                <input type="text" name="name" required placeholder="e.g. Colombo Central Inbound Clearance" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Target Module / Entity *</label>
                <select name="entity_type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="StockDispatch">Stock Dispatches (Outbound Issue / Requisition)</option>
                    <option value="StockReceipt">Stock Receipts (Inbound Receiving / Lot Addition)</option>
                    <option value="StockTransfer">Inter-Warehouse Transfer Process</option>
                    <option value="StockMovement">General Stock Movement Pipeline</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Select the lifecycle process this approval machine controls.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Facility / Warehouse Location (Scope)</label>
                <select name="warehouse_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="">-- All Locations (Global Scope) --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }}) @if($wh->location) - {{ $wh->location }} @endif</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Restrict this workflow to a specific depot/location or apply across all warehouses.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Operational guidelines, stage locations, and purpose..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addWorkflowModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Launch Builder</button>
            </div>
        </form>
    </div>
</div>
@endsection
