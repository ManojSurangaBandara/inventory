@extends('layouts.app')

@section('title', 'Inventory Items Catalog')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Inventory Items Catalog</h2>
            <p class="text-xs text-slate-400">Scoped to organization database records.</p>
        </div>
        <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Add New Item</span>
        </button>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-4 flex flex-col md:flex-row items-center justify-between gap-3">
        <form action="{{ route('inventory.items') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search SKU or item name..." class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 w-full sm:w-64">

            <select name="category_id" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="low_stock" value="1" onchange="this.form.submit()" {{ request('low_stock') ? 'checked' : '' }} class="rounded bg-slate-950 border-slate-800 text-indigo-600">
                <span class="text-rose-400 font-medium">Low Stock Only</span>
            </label>

            <button type="submit" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold">Filter</button>
            @if(request()->hasAny(['search', 'category_id', 'low_stock']))
                <a href="{{ route('inventory.items') }}" class="text-xs text-slate-400 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Items Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">SKU</th>
                        <th class="px-4 py-3.5">Item Name</th>
                        <th class="px-4 py-3.5">Category</th>
                        <th class="px-4 py-3.5">Unit Cost</th>
                        <th class="px-4 py-3.5">Current Stock</th>
                        <th class="px-4 py-3.5">Stock Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4 font-mono font-bold text-indigo-400">{{ $item->sku }}</td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-white text-sm">{{ $item->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ Str::limit($item->description, 40) ?? 'No description' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $item->category->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-white">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-4 py-4 font-bold text-sm text-white">
                                {{ $item->current_stock }} <span class="text-xs font-normal text-slate-400">{{ $item->unit }}</span>
                            </td>
                            <td class="px-4 py-4">
                                @if($item->isLowStock())
                                    <span class="px-2.5 py-1 rounded-full text-[10px] bg-rose-500/10 text-rose-400 border border-rose-500/30 font-bold animate-pulse">Low Stock</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold">Optimal</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right space-x-2">
                                <button onclick="editItem({{ json_encode($item) }})" class="px-3 py-1.5 rounded-xl border border-slate-700 text-[11px] text-slate-300 hover:bg-slate-800 transition">
                                    Edit
                                </button>
                                <form action="{{ route('inventory.items.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this inventory item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1.5 text-rose-400 hover:text-rose-300 text-[11px]">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No inventory items found in your catalog.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </div>
</div>

<!-- Modal: Add Item -->
<div id="addItemModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add New Inventory Item</h3>
            <button onclick="document.getElementById('addItemModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('inventory.items.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">SKU Code *</label>
                    <input type="text" name="sku" required placeholder="e.g. LAP-001" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Item Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Dell XPS 15 Laptop" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
                    <select name="category_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Uncategorized</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unit of Measure *</label>
                    <input type="text" name="unit" required value="pcs" placeholder="pcs, boxes, kg" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unit Cost ($) *</label>
                    <input type="number" step="0.01" name="unit_cost" required value="0.00" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Reorder Level *</label>
                    <input type="number" name="reorder_level" required value="10" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Current Stock *</label>
                    <input type="number" name="current_stock" required value="50" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Item Description</label>
                <textarea name="description" rows="2" placeholder="Item specs, storage notes..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Item -->
<div id="editItemModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Inventory Item</h3>
            <button onclick="document.getElementById('editItemModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editItemForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">SKU Code *</label>
                    <input type="text" name="sku" id="edit_sku" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Item Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
                    <select name="category_id" id="edit_category_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Uncategorized</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unit of Measure *</label>
                    <input type="text" name="unit" id="edit_unit" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unit Cost ($) *</label>
                    <input type="number" step="0.01" name="unit_cost" id="edit_unit_cost" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Reorder Level *</label>
                    <input type="number" name="reorder_level" id="edit_reorder_level" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Current Stock *</label>
                    <input type="number" name="current_stock" id="edit_current_stock" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Item Description</label>
                <textarea name="description" id="edit_description" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editItemModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Update Item</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editItem(item) {
        document.getElementById('editItemForm').action = "{{ url('/inventory/items') }}/" + item.id;
        document.getElementById('edit_sku').value = item.sku;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_category_id').value = item.category_id || '';
        document.getElementById('edit_unit').value = item.unit;
        document.getElementById('edit_unit_cost').value = item.unit_cost;
        document.getElementById('edit_reorder_level').value = item.reorder_level;
        document.getElementById('edit_current_stock').value = item.current_stock;
        document.getElementById('edit_description').value = item.description || '';

        document.getElementById('editItemModal').classList.remove('hidden');
    }
</script>
@endsection
