@extends('layouts.app')

@section('title', 'Dynamic UI Workflow Engine')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Configured Workflows</h2>
            <p class="text-xs text-slate-400">Design lifecycle states, status transitions, and role approval rules for inventory processes.</p>
        </div>
        <button onclick="document.getElementById('addWorkflowModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create New Workflow</span>
        </button>
    </div>

    <!-- Workflows Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($workflows as $wf)
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4 hover:border-indigo-500/30 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-[10px] bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold uppercase tracking-wider">
                            {{ $wf->entity_type }}
                        </span>
                        <span class="flex items-center space-x-1 text-[10px] text-emerald-400 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>Active</span>
                        </span>
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

                <a href="{{ route('workflows.builder', $wf->id) }}" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold text-center transition shadow-md shadow-indigo-600/20 block">
                    Open Visual Builder &rarr;
                </a>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-slate-800/80 mx-auto flex items-center justify-center text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="text-white font-bold text-sm">No Workflows Defined</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">Create custom workflows to control approval steps and status changes for Stock Movements and Purchase Orders.</p>
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
                <input type="text" name="name" required placeholder="e.g. Stock Receipt Inspection Workflow" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Target Module / Entity *</label>
                <select name="entity_type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="StockMovement">Stock Movement Lifecycle</option>
                    <option value="PurchaseOrder">Purchase Order Lifecycle</option>
                    <option value="InventoryItem">Inventory Item Lifecycle</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Purpose and guidelines..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addWorkflowModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Launch Builder</button>
            </div>
        </form>
    </div>
</div>
@endsection
