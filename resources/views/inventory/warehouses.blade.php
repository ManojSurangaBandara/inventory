@extends('layouts.app')

@section('title', 'Warehouses Management')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Storage Warehouses & Facilities</h2>
            <p class="text-xs text-slate-400">Classify storage depots, configure custom warehouse types, track localized inventory, and manage personnel access.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="document.getElementById('manageTypesModal').classList.remove('hidden')" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold rounded-xl text-xs transition flex items-center space-x-1.5 shadow-sm">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>⚙️ Warehouse Types ({{ $warehouseTypes->count() }})</span>
            </button>
            <button onclick="document.getElementById('addWHModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ New Warehouse</span>
            </button>
        </div>
    </div>

    <!-- Warehouse Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
        @forelse($warehouses as $wh)
            @php
                $hasDependencies = ($wh->stock_movements_count > 0) || ($wh->purchase_orders_count > 0);
            @endphp
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-4 hover:border-indigo-500/30 transition">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $wh->type_badge_class }}">
                                    {{ $wh->type_label }}
                                </span>
                            </div>
                            <h3 class="font-bold text-white text-base mt-1.5">{{ $wh->name }}</h3>
                            <span class="font-mono text-[10px] text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20 font-semibold inline-block mt-0.5">
                                {{ $wh->code }}
                            </span>
                        </div>
                        
                        <div class="flex items-center space-x-1">
                            <!-- Edit Warehouse Button -->
                            <button type="button" onclick='openEditWHModal({{ json_encode($wh) }})' class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Warehouse">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>

                            @if(!$hasDependencies)
                                <!-- Delete Warehouse Button (Only shown for unused / deletable warehouses) -->
                                <form action="{{ route('inventory.warehouses.destroy', $wh->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete mistakenly created warehouse \'{{ $wh->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-lg transition" title="Delete unused warehouse">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="text-xs text-slate-300 flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="truncate">{{ $wh->location ?? 'Main Facility Depot' }}</span>
                    </div>

                    <!-- Usage & Dependency Counters -->
                    <div class="pt-3 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-xs">
                        <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800/60 space-y-0.5">
                            <span class="text-[9px] text-slate-400 block font-medium">Stocked SKUs</span>
                            <span class="text-xs font-bold text-emerald-400">
                                {{ $wh->stocks_count ?? 0 }} SKUs
                            </span>
                        </div>
                        <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800/60 space-y-0.5">
                            <span class="text-[9px] text-slate-400 block font-medium">Requisitions</span>
                            <span class="text-xs font-bold {{ $wh->stock_movements_count > 0 ? 'text-indigo-400' : 'text-slate-500' }}">
                                {{ $wh->stock_movements_count }}
                            </span>
                        </div>
                        <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800/60 space-y-0.5">
                            <span class="text-[9px] text-slate-400 block font-medium">PO Orders</span>
                            <span class="text-xs font-bold {{ $wh->purchase_orders_count > 0 ? 'text-purple-400' : 'text-slate-500' }}">
                                {{ $wh->purchase_orders_count }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-1 flex items-center justify-between text-[11px]">
                    @if($hasDependencies)
                        <span class="text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Active Facility
                        </span>
                    @else
                        <span class="text-slate-400 bg-slate-800/80 px-2 py-0.5 rounded-full border border-slate-700 font-medium">
                            No Active Transactions
                        </span>
                    @endif
                    <span class="text-[10px] text-slate-500 font-mono">ID: #{{ $wh->id }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                No storage warehouses defined yet. Click "+ New Warehouse" to add facilities.
            </div>
        @endempty
    </div>
</div>

<!-- Modal: Add Warehouse -->
<div id="addWHModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add Storage Warehouse</h3>
            <button onclick="document.getElementById('addWHModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('inventory.warehouses.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Name *</label>
                <input type="text" name="name" required placeholder="e.g. Central Supply Depot" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Code *</label>
                    <input type="text" name="code" required placeholder="e.g. WH-CMB-01" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Type *</label>
                    <select name="warehouse_type_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                        @foreach($warehouseTypes as $wt)
                            <option value="{{ $wt->id }}">[{{ $wt->code }}] {{ $wt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Physical Location Address</label>
                <input type="text" name="location" placeholder="e.g. Building 4, Logistics Park, Colombo" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addWHModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Warehouse</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Warehouse -->
<div id="editWHModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Warehouse Details</h3>
            <button onclick="document.getElementById('editWHModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editWHForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Name *</label>
                <input type="text" name="name" id="edit_wh_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Code *</label>
                    <input type="text" name="code" id="edit_wh_code" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Type *</label>
                    <select name="warehouse_type_id" id="edit_wh_type_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                        @foreach($warehouseTypes as $wt)
                            <option value="{{ $wt->id }}">[{{ $wt->code }}] {{ $wt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Physical Location Address</label>
                <input type="text" name="location" id="edit_wh_location" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editWHModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Update Warehouse</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Manage Configurable Warehouse Types -->
<div id="manageTypesModal" class="hidden fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <span>⚙️ Configurable Warehouse Types</span>
                </h3>
                <p class="text-[11px] text-slate-400">Define and customize warehouse classifications (e.g. Main Hub, Unit Workshop, Cold Storage) for your organization.</p>
            </div>
            <button onclick="document.getElementById('manageTypesModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <!-- Existing Types List -->
        <div class="space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Configured Types ({{ $warehouseTypes->count() }})</h4>
            <div class="divide-y divide-slate-800/80 border border-slate-800 rounded-2xl overflow-hidden bg-slate-950/40">
                @forelse($warehouseTypes as $wt)
                    @php
                        $linkedCount = $wt->warehouses()->count();
                    @endphp
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-800/30 transition">
                        <div class="flex items-center space-x-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $wt->badge_class }}">
                                {{ $wt->name }}
                            </span>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">{{ $wt->code }}</span>
                                    <span class="text-xs text-slate-300 font-medium">• {{ $linkedCount }} linked {{ Str::plural('facility', $linkedCount) }}</span>
                                </div>
                                @if($wt->description)
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $wt->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center space-x-1.5">
                            <!-- Edit Type Button -->
                            <button type="button" onclick='openEditTypeModal({{ json_encode($wt) }})' class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Type">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>

                            <!-- Delete Type Button -->
                            @if($linkedCount === 0)
                                <form action="{{ route('inventory.warehouse-types.destroy', $wt->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete warehouse type \'{{ $wt->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-lg transition" title="Delete Unused Type">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @else
                                <span class="p-1.5 text-slate-600 cursor-not-allowed" title="Cannot delete: linked to active warehouses">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-xs text-slate-500">No warehouse types found.</div>
                @endforelse
            </div>
        </div>

        <!-- Add New Type Form -->
        <div class="border-t border-slate-800 pt-4 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-400">+ Add Custom Warehouse Type</h4>
            <form action="{{ route('inventory.warehouse-types.store') }}" method="POST" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Type Code *</label>
                        <input type="text" name="code" required placeholder="e.g. COLD" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Type Display Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Cold Storage Facility" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Badge Color Theme *</label>
                        <select name="color" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                            <option value="emerald">🟢 Emerald Green</option>
                            <option value="blue">🔵 Azure Blue</option>
                            <option value="amber">🟡 Amber Yellow</option>
                            <option value="purple">🟣 Royal Purple</option>
                            <option value="rose">🔴 Rose Coral</option>
                            <option value="cyan">🌊 Cyan Teal</option>
                            <option value="indigo">🌌 Deep Indigo</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description (Optional)</label>
                        <input type="text" name="description" placeholder="e.g. Temperature controlled storage depot" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition">
                        Add Warehouse Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Warehouse Type -->
<div id="editTypeModal" class="hidden fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Warehouse Type</h3>
            <button onclick="document.getElementById('editTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editTypeForm" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Type Code *</label>
                    <input type="text" name="code" id="edit_type_code" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Badge Color *</label>
                    <select name="color" id="edit_type_color" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                        <option value="emerald">🟢 Emerald Green</option>
                        <option value="blue">🔵 Azure Blue</option>
                        <option value="amber">🟡 Amber Yellow</option>
                        <option value="purple">🟣 Royal Purple</option>
                        <option value="rose">🔴 Rose Coral</option>
                        <option value="cyan">🌊 Cyan Teal</option>
                        <option value="indigo">🌌 Deep Indigo</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Display Name *</label>
                <input type="text" name="name" id="edit_type_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <input type="text" name="description" id="edit_type_description" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editTypeModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditWHModal(wh) {
        document.getElementById('editWHForm').action = "{{ url('/inventory/warehouses') }}/" + wh.id;
        document.getElementById('edit_wh_name').value = wh.name;
        document.getElementById('edit_wh_code').value = wh.code;
        document.getElementById('edit_wh_type_id').value = wh.warehouse_type_id || '';
        document.getElementById('edit_wh_location').value = wh.location || '';
        document.getElementById('editWHModal').classList.remove('hidden');
    }

    function openEditTypeModal(wt) {
        document.getElementById('editTypeForm').action = "{{ url('/inventory/warehouse-types') }}/" + wt.id;
        document.getElementById('edit_type_name').value = wt.name;
        document.getElementById('edit_type_code').value = wt.code;
        document.getElementById('edit_type_color').value = wt.color || 'emerald';
        document.getElementById('edit_type_description').value = wt.description || '';
        document.getElementById('editTypeModal').classList.remove('hidden');
    }
</script>
@endsection
