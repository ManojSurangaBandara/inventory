@php
    $children = $warehousesByParent->get($wh->id, collect());
    $hasDependencies = ($wh->stock_movements_count > 0) || ($wh->purchase_orders_count > 0) || ($children->count() > 0);
    $level = $level ?? 0;
@endphp

<div class="tree-node relative {{ $level > 0 ? 'ml-4 sm:ml-8 mt-3' : 'mt-4' }}" data-node-id="{{ $wh->id }}">
    @if($level > 0)
        <!-- Connector lines: elbow curve from parent vertical trunk -->
        <div class="absolute -left-4 sm:-left-8 top-6 w-4 sm:w-8 h-6 border-b-2 border-l-2 border-slate-700/80 rounded-bl-xl pointer-events-none"></div>
    @endif

    <div class="bg-slate-900/90 border border-slate-800 hover:border-indigo-500/40 rounded-2xl p-4 shadow-xl transition duration-200 {{ $level === 0 ? 'border-l-4 border-l-emerald-500' : ($level === 1 ? 'border-l-4 border-l-indigo-500' : 'border-l-4 border-l-amber-500') }}">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Node Identity & Hierarchy Info -->
            <div class="flex items-start sm:items-center space-x-3">
                @if($children->count() > 0)
                    <button type="button" onclick="toggleTreeNode({{ $wh->id }})" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition shrink-0 mt-0.5 sm:mt-0 shadow-sm" title="Expand / Collapse Sub-facilities">
                        <svg id="icon-collapse-{{ $wh->id }}" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                @else
                    <div class="w-7 h-7 rounded-lg bg-slate-950 flex items-center justify-center shrink-0 border border-slate-800/80">
                        <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                    </div>
                @endif

                <div class="space-y-1">
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        @if($level === 0)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Tier 1: Primary Central Hub
                            </span>
                        @elseif($level === 1)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                Tier 2: Regional Sub-Depot
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                Tier {{ $level + 1 }}: Field Workshop / Unit
                            </span>
                        @endif

                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider {{ $wh->type_badge_class }}">
                            {{ $wh->type_label }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <h4 class="font-bold text-white text-base">{{ $wh->name }}</h4>
                        <span class="font-mono text-xs text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20 font-semibold">
                            {{ $wh->code }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-400 flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="truncate">{{ $wh->location ?? 'Main Facility Location' }}</span>
                    </div>
                </div>
            </div>

            <!-- Metrics & Toolbar Actions -->
            <div class="flex items-center justify-between lg:justify-end space-x-3 pt-2 lg:pt-0 border-t border-slate-800/80 lg:border-none flex-wrap gap-y-2">
                <!-- Live KPI Pills -->
                <div class="flex items-center space-x-2 text-xs">
                    <div class="bg-slate-950/80 px-2.5 py-1 rounded-xl border border-slate-800 text-[11px]">
                        <span class="text-slate-400">Stocked:</span>
                        <span class="font-bold text-emerald-400 ml-1">{{ $wh->stocks_count ?? 0 }} SKUs</span>
                    </div>
                    <div class="bg-slate-950/80 px-2.5 py-1 rounded-xl border border-slate-800 text-[11px]">
                        <span class="text-slate-400">Requisitions:</span>
                        <span class="font-bold {{ $wh->stock_movements_count > 0 ? 'text-indigo-400' : 'text-slate-500' }} ml-1">{{ $wh->stock_movements_count }}</span>
                    </div>
                    @if($children->count() > 0)
                        <div class="bg-emerald-500/10 px-2.5 py-1 rounded-xl border border-emerald-500/20 text-[11px] text-emerald-300 font-semibold flex items-center space-x-1">
                            <svg class="w-3 h-3 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>{{ $children->count() }} Sub-facilities</span>
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-1.5">
                    <!-- Add Child Warehouse Button -->
                    <button type="button" onclick="openAddSubFacilityModal({{ $wh->id }}, '{{ addslashes($wh->name) }}', '{{ addslashes($wh->code) }}')" class="px-2.5 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 rounded-xl text-xs font-semibold transition flex items-center space-x-1 shadow-sm" title="Add child facility directly under {{ $wh->name }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Sub-Facility</span>
                    </button>

                    <!-- Edit Warehouse Button -->
                    <button type="button" onclick='openEditWHModal({{ json_encode($wh) }}, {{ json_encode($wh->allDescendantIds()) }})' class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Warehouse Details">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>

                    @if(!$hasDependencies)
                        <!-- Delete Warehouse Button -->
                        <form action="{{ route('inventory.warehouses.destroy', $wh->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete unused warehouse \'{{ $wh->name }}\'?')">
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
        </div>
    </div>

    <!-- Recursive Children Container with Vertical Trunk Line -->
    @if($children->count() > 0)
        <div id="children-container-{{ $wh->id }}" class="relative pl-2 sm:pl-4 border-l-2 border-slate-700/60 ml-4 sm:ml-6 mt-1 space-y-3">
            @foreach($children as $child)
                @include('inventory.partials.warehouse_tree_node', [
                    'wh' => $child,
                    'warehousesByParent' => $warehousesByParent,
                    'level' => $level + 1,
                ])
            @endforeach
        </div>
    @endif
</div>
