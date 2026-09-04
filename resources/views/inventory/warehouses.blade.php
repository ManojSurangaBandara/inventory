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
        <div class="flex items-center space-x-3 flex-wrap gap-y-2">
            <!-- View Mode Switcher -->
            <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl p-1 text-xs font-semibold shadow-sm">
                <button type="button" id="btnViewGrid" onclick="switchWarehouseView('grid')" class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 bg-indigo-600 text-white shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Grid View</span>
                </button>
                <button type="button" id="btnViewTree" onclick="switchWarehouseView('tree')" class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 text-slate-400 hover:text-white">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2zM9 13h6m-3-3v6"/></svg>
                    <span>Hierarchy Tree</span>
                </button>
            </div>

            <a href="{{ route('inventory.warehouse-types') }}" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold rounded-xl text-xs transition flex items-center space-x-1.5 shadow-sm">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Warehouse Types ({{ $warehouseTypes->count() }})</span>
            </a>
            <button onclick="openAddWHModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Warehouse</span>
            </button>
        </div>
    </div>

    <!-- VIEW 1: Warehouse Cards Grid -->
    <div id="warehouseGridView" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
        @forelse($warehouses as $wh)
            @php
                $hasDependencies = ($wh->stock_movements_count > 0) || ($wh->purchase_orders_count > 0) || (($wh->children_count ?? 0) > 0);
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
                            <button type="button" onclick='openEditWHModal({{ json_encode($wh) }}, {{ json_encode($wh->allDescendantIds()) }})' class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Warehouse">
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

                    <!-- Hierarchy Badges -->
                    @if($wh->parent)
                        <div class="text-xs text-indigo-300 bg-indigo-950/40 border border-indigo-800/40 px-2.5 py-1.5 rounded-xl flex items-center space-x-2">
                            <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                            </svg>
                            <span class="text-[11px] truncate">
                                Reports to: <strong class="text-indigo-200">{{ $wh->parent->name }}</strong>
                                <span class="font-mono text-[10px] text-indigo-400">({{ $wh->parent->code }})</span>
                            </span>
                        </div>
                    @endif

                    @if(($wh->children_count ?? 0) > 0)
                        <div class="text-xs text-emerald-300 bg-emerald-950/30 border border-emerald-800/40 px-2.5 py-1.5 rounded-xl flex items-center space-x-2">
                            <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span class="text-[11px] truncate">
                                Parent Hub: <strong class="text-emerald-200">{{ $wh->children_count }}</strong> sub-facility{{ $wh->children_count > 1 ? 'ies' : '' }} attached
                            </span>
                        </div>
                    @endif

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
        @endforelse
    </div>

    <!-- VIEW 2: Warehouse Hierarchy Tree View -->
    <div id="warehouseTreeView" class="hidden space-y-4">
        <!-- Tree Controls & Summary Header -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-4 sm:p-5 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Facility Network Topology</h3>
                    <p class="text-xs text-slate-400">Interactive visual hierarchy tree of central supply hubs, regional branches, and field workshop units.</p>
                </div>
            </div>

            <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                <button type="button" onclick="expandAllTreeNodes()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700">
                    Expand All
                </button>
                <button type="button" onclick="collapseAllTreeNodes()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition border border-slate-700">
                    Collapse All
                </button>
            </div>
        </div>

        <!-- Tree Nodes List -->
        <div class="space-y-4">
            @forelse($rootWarehouses as $rootWh)
                @include('inventory.partials.warehouse_tree_node', [
                    'wh' => $rootWh,
                    'warehousesByParent' => $warehousesByParent,
                    'level' => 0,
                ])
            @empty
                <div class="bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                    No storage warehouses defined yet. Click "+ New Warehouse" to add facilities.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal: Add Warehouse -->
<div id="addWHModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add Storage Warehouse</h3>
            <button onclick="document.getElementById('addWHModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <!-- Dynamic Parent Notice (When opened from Tree View "+ Add Sub-Facility") -->
        <div id="addWHModalParentBanner" class="hidden bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 text-xs text-indigo-300 flex items-center space-x-2">
            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
            <div class="truncate">
                Adding sub-facility reporting to: <strong id="addWHModalParentName" class="text-white"></strong>
            </div>
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
                <label class="block text-xs font-semibold text-slate-300 mb-1">Parent Facility / Central Hub (Optional)</label>
                <select name="parent_warehouse_id" id="add_wh_parent_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                    <option value="">-- None (Top-Level Primary Facility) --</option>
                    @foreach($warehouses as $pWh)
                        <option value="{{ $pWh->id }}">{{ $pWh->name }} ({{ $pWh->code }}) &bull; {{ $pWh->type_label }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Designate a primary supply depot or central warehouse this facility reports to.</p>
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
                <label class="block text-xs font-semibold text-slate-300 mb-1">Parent Facility / Central Hub (Optional)</label>
                <select name="parent_warehouse_id" id="edit_parent_warehouse_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-semibold">
                    <option value="">-- None (Top-Level Primary Facility) --</option>
                    @foreach($warehouses as $pWh)
                        <option value="{{ $pWh->id }}" data-wh-id="{{ $pWh->id }}">{{ $pWh->name }} ({{ $pWh->code }}) &bull; {{ $pWh->type_label }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Designate a primary supply depot. Circular parent dependencies are disabled.</p>
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
    function switchWarehouseView(view) {
        const gridView = document.getElementById('warehouseGridView');
        const treeView = document.getElementById('warehouseTreeView');
        const btnGrid = document.getElementById('btnViewGrid');
        const btnTree = document.getElementById('btnViewTree');

        if (view === 'tree') {
            gridView.classList.add('hidden');
            treeView.classList.remove('hidden');
            btnTree.className = 'px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 bg-indigo-600 text-white shadow-sm';
            btnGrid.className = 'px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 text-slate-400 hover:text-white';
            localStorage.setItem('warehouse_view_preference', 'tree');
        } else {
            treeView.classList.add('hidden');
            gridView.classList.remove('hidden');
            btnGrid.className = 'px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 bg-indigo-600 text-white shadow-sm';
            btnTree.className = 'px-3 py-1.5 rounded-lg transition flex items-center space-x-1.5 text-slate-400 hover:text-white';
            localStorage.setItem('warehouse_view_preference', 'grid');
        }
    }

    function toggleTreeNode(whId) {
        const container = document.getElementById('children-container-' + whId);
        const icon = document.getElementById('icon-collapse-' + whId);
        if (container) {
            container.classList.toggle('hidden');
            if (icon) {
                icon.classList.toggle('-rotate-90');
            }
        }
    }

    function expandAllTreeNodes() {
        document.querySelectorAll('[id^="children-container-"]').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('[id^="icon-collapse-"]').forEach(el => el.classList.remove('-rotate-90'));
    }

    function collapseAllTreeNodes() {
        document.querySelectorAll('[id^="children-container-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="icon-collapse-"]').forEach(el => el.classList.add('-rotate-90'));
    }

    function openAddWHModal() {
        const parentSelect = document.getElementById('add_wh_parent_id');
        if (parentSelect) {
            parentSelect.value = '';
        }
        const banner = document.getElementById('addWHModalParentBanner');
        if (banner) {
            banner.classList.add('hidden');
        }
        document.getElementById('addWHModal').classList.remove('hidden');
    }

    function openAddSubFacilityModal(parentId, parentName, parentCode) {
        const parentSelect = document.getElementById('add_wh_parent_id');
        if (parentSelect) {
            parentSelect.value = parentId;
        }
        const banner = document.getElementById('addWHModalParentBanner');
        const bannerText = document.getElementById('addWHModalParentName');
        if (banner && bannerText) {
            bannerText.textContent = parentName + ' (' + parentCode + ')';
            banner.classList.remove('hidden');
        }
        document.getElementById('addWHModal').classList.remove('hidden');
    }

    function openEditWHModal(wh, descendantIds = []) {
        document.getElementById('editWHForm').action = "{{ url('/inventory/warehouses') }}/" + wh.id;
        document.getElementById('edit_wh_name').value = wh.name;
        document.getElementById('edit_wh_code').value = wh.code;
        document.getElementById('edit_wh_type_id').value = wh.warehouse_type_id || '';
        document.getElementById('edit_wh_location').value = wh.location || '';

        const parentSelect = document.getElementById('edit_parent_warehouse_id');
        if (parentSelect) {
            Array.from(parentSelect.options).forEach(opt => {
                const optVal = parseInt(opt.getAttribute('data-wh-id'));
                if (optVal && (optVal === wh.id || (Array.isArray(descendantIds) && descendantIds.includes(optVal)))) {
                    opt.disabled = true;
                    opt.hidden = true;
                } else {
                    opt.disabled = false;
                    opt.hidden = false;
                }
            });
            parentSelect.value = wh.parent_warehouse_id || '';
        }

        document.getElementById('editWHModal').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const pref = localStorage.getItem('warehouse_view_preference');
        if (pref === 'tree') {
            switchWarehouseView('tree');
        }
    });
</script>
@endsection
