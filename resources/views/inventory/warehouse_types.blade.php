@extends('layouts.app')

@section('title', 'Warehouse Types - Master Data')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase tracking-wider">
                    Master Data Catalog
                </span>
            </div>
            <h2 class="text-xl font-bold text-white mt-1">Warehouse Types</h2>
            <p class="text-xs text-slate-400">Manage warehouse classifications used to classify your organization's storage facilities.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('inventory.warehouses') }}" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold rounded-xl text-xs transition flex items-center space-x-1.5 shadow-sm">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>View Warehouses</span>
            </a>
            <button onclick="document.getElementById('addTypeModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Warehouse Type</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs text-slate-400 font-medium">Configured Types</span>
                <div class="text-2xl font-bold text-white">{{ $totalTypes }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">
                🏷️
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs text-slate-400 font-medium">Linked Storage Facilities</span>
                <div class="text-2xl font-bold text-emerald-400">{{ $totalLinked }}</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold">
                🏢
            </div>
        </div>
    </div>

    <!-- Warehouse Types Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">Warehouse Type Name</th>
                        <th class="px-4 py-3.5">Linked Facilities</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($warehouseTypes as $wt)
                        @php
                            $linkedCount = $wt->warehouses_count ?? $wt->warehouses()->count();
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Type Name -->
                            <td class="px-4 py-4">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-white text-sm">{{ $wt->name }}</span>
                                    @if($wt->is_default)
                                        <span class="text-[9px] bg-slate-800 text-slate-400 px-1.5 py-0.5 rounded border border-slate-700 font-semibold">Default</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Linked Facilities -->
                            <td class="px-4 py-4">
                                @if($linkedCount > 0)
                                    <a href="{{ route('inventory.warehouses') }}" class="font-bold text-indigo-400 hover:underline flex items-center gap-1">
                                        <span>{{ $linkedCount }} {{ Str::plural('Facility', $linkedCount) }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-500">0 facilities</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    <!-- Edit Button -->
                                    <button type="button" onclick='openEditTypeModal({{ json_encode($wt) }})' class="p-1.5 text-slate-400 hover:text-indigo-400 hover:bg-slate-800 rounded-lg transition" title="Edit Warehouse Type Name">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>

                                    <!-- Delete Button -->
                                    @if($linkedCount === 0)
                                        <form action="{{ route('inventory.warehouse-types.destroy', $wt->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete warehouse type \'{{ $wt->name }}\'?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-lg transition" title="Delete Unused Type">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="p-1.5 text-slate-600 cursor-not-allowed" title="Cannot delete: linked to {{ $linkedCount }} active warehouse(s)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-slate-500">No warehouse types found. Click "+ New Warehouse Type" to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Warehouse Type (Only Name) -->
<div id="addTypeModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add Warehouse Type</h3>
            <button onclick="document.getElementById('addTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form action="{{ route('inventory.warehouse-types.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Type Name *</label>
                <input type="text" name="name" required placeholder="e.g. Central Depot, Regional Hub, Workshop Unit, Cold Storage" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addTypeModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Type</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Warehouse Type (Only Name) -->
<div id="editTypeModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Warehouse Type</h3>
            <button onclick="document.getElementById('editTypeModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <form id="editTypeForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Warehouse Type Name *</label>
                <input type="text" name="name" id="edit_type_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editTypeModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Update Type</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditTypeModal(wt) {
        document.getElementById('editTypeForm').action = "{{ url('/inventory/warehouse-types') }}/" + wt.id;
        document.getElementById('edit_type_name').value = wt.name;
        document.getElementById('editTypeModal').classList.remove('hidden');
    }
</script>
@endsection
