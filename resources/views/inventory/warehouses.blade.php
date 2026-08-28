@extends('layouts.app')

@section('title', 'Warehouses Management')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Storage Warehouses & Facilities</h2>
            <p class="text-xs text-slate-400">Classify Main, Sub, and Unit warehouses, track localized inventory, and manage personnel access.</p>
        </div>
        <button onclick="document.getElementById('addWHModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ New Warehouse</span>
        </button>
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
                            <div class="flex items-center space-x-2">
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
                    <select name="type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                        <option value="main">Main Warehouse (Central)</option>
                        <option value="sub">Sub Warehouse (Regional)</option>
                        <option value="unit">Unit Warehouse (Workshop/Field)</option>
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
                    <select name="type" id="edit_wh_type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                        <option value="main">Main Warehouse (Central)</option>
                        <option value="sub">Sub Warehouse (Regional)</option>
                        <option value="unit">Unit Warehouse (Workshop/Field)</option>
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

<script>
    function openEditWHModal(wh) {
        document.getElementById('editWHForm').action = "{{ url('/inventory/warehouses') }}/" + wh.id;
        document.getElementById('edit_wh_name').value = wh.name;
        document.getElementById('edit_wh_code').value = wh.code;
        document.getElementById('edit_wh_type').value = wh.type || 'main';
        document.getElementById('edit_wh_location').value = wh.location || '';
        document.getElementById('editWHModal').classList.remove('hidden');
    }
</script>
@endsection
