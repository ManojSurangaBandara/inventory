@extends('layouts.app')

@section('title', 'Items')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Items</h2>
            <p class="text-xs text-slate-400">Manage product items with 4-level category classifications (Category 1 required, Categories 2-4 optional).</p>
        </div>
        <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Item</span>
        </button>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
        <form action="{{ route('inventory.items') }}" method="GET" class="flex-1 flex flex-wrap items-center gap-3 w-full">
            <div class="relative flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by SKU, Product Name, or Specs..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs text-white focus:border-indigo-500 focus:outline-none">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Category 1 Filter -->
                <select name="category_id" id="filter_cat_1" onchange="cascadeCategories('filter', 1, this.value); this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white">
                    <option value="">All Category 1</option>
                    @foreach($category1List as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>

                <!-- Category 2 Filter -->
                <select name="category_2_id" id="filter_cat_2" onchange="cascadeCategories('filter', 2, this.value); this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white">
                    <option value="">All Category 2</option>
                    @foreach($category2List as $cat)
                        @if(!request('category_id') || $cat->parent_id == request('category_id'))
                            <option value="{{ $cat->id }}" {{ request('category_2_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endif
                    @endforeach
                </select>

                <!-- Category 3 Filter -->
                <select name="category_3_id" id="filter_cat_3" onchange="cascadeCategories('filter', 3, this.value); this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white">
                    <option value="">All Category 3</option>
                    @foreach($category3List as $cat)
                        @if(!request('category_2_id') || $cat->parent_id == request('category_2_id'))
                            <option value="{{ $cat->id }}" {{ request('category_3_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endif
                    @endforeach
                </select>

                <!-- Category 4 Filter -->
                <select name="category_4_id" id="filter_cat_4" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-2.5 py-2 text-xs text-white">
                    <option value="">All Category 4</option>
                    @foreach($category4List as $cat)
                        @if(!request('category_3_id') || $cat->parent_id == request('category_3_id'))
                            <option value="{{ $cat->id }}" {{ request('category_4_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="low_stock" value="1" onchange="this.form.submit()" {{ request('low_stock') ? 'checked' : '' }} class="rounded bg-slate-950 border-slate-800 text-indigo-600">
                <span class="text-rose-400 font-semibold">Low Stock Only</span>
            </label>

            <button type="submit" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-700">Filter</button>
            @if(request()->hasAny(['search', 'category_id', 'category_2_id', 'category_3_id', 'category_4_id', 'low_stock']))
                <a href="{{ route('inventory.items') }}" class="text-xs text-slate-500 hover:text-slate-300 underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Items Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">SKU & Item Name</th>
                        <th class="px-4 py-3.5">Categories (1 - 4)</th>
                        <th class="px-4 py-3.5">Unit & Cost</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4">
                                <div class="font-bold text-white text-sm">{{ $item->name }}</div>
                                <div class="font-mono text-indigo-400 text-[11px]">{{ $item->sku }}</div>
                                @if($item->description)
                                    <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-xs font-semibold text-slate-200">
                                    {{ $item->category1->name ?? 'Uncategorized' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono flex items-center space-x-1 mt-0.5">
                                    @if($item->category2)
                                        <span>&rarr; {{ $item->category2->name }}</span>
                                    @endif
                                    @if($item->category3)
                                        <span>&rarr; {{ $item->category3->name }}</span>
                                    @endif
                                    @if($item->category4)
                                        <span>&rarr; {{ $item->category4->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-white">Rs. {{ number_format($item->unit_cost, 2) }}</div>
                                <div class="text-[10px] text-slate-400">per {{ $item->unit }}</div>
                            </td>
                            <td class="px-4 py-4 text-right space-x-2">
                                @if(!$item->isUsed())
                                    <button onclick='editItem({{ json_encode($item) }})' class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-lg text-xs font-medium transition">
                                        Edit
                                    </button>
                                    <form action="{{ route('inventory.items.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete item \'{{ $item->name }}\'?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg text-xs font-medium transition">
                                            Delete
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded bg-slate-800/40 text-slate-500 text-[10px] font-medium" title="Item is actively in use or has transaction history and cannot be edited or deleted">
                                        <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span>In Use</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No master items registered in catalog.</td>
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

<!-- Modal: Register Master Item -->
<div id="addItemModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
                <h3 class="font-bold text-white text-base">Add New Item</h3>
                <p class="text-[11px] text-slate-400">Fill in product details and configure Category 1 (Required) and Categories 2-4 (Optional).</p>
            </div>
            <button onclick="document.getElementById('addItemModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('inventory.items.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- 4-Level Category Selectors -->
            <div class="p-4 bg-slate-950/80 border border-slate-800 rounded-2xl space-y-3">
                <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Category Levels (1 to 4)</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 1 * (Required)</label>
                        <select name="category_id" id="add_cat_1" onchange="cascadeCategories('add', 1, this.value)" required class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- Select Category 1 --</option>
                            @foreach($category1List as $c1)
                                <option value="{{ $c1->id }}">{{ $c1->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 2 (Optional)</label>
                        <select name="category_2_id" id="add_cat_2" onchange="cascadeCategories('add', 2, this.value)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- None / Select Category 1 First --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 3 (Optional)</label>
                        <select name="category_3_id" id="add_cat_3" onchange="cascadeCategories('add', 3, this.value)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- None / Select Category 2 First --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 4 (Optional)</label>
                        <select name="category_4_id" id="add_cat_4" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- None / Select Category 3 First --</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Product Specs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Product SKU / Code *</label>
                    <input type="text" name="sku" required placeholder="e.g. LAP-XPS15" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Product Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Dell XPS 15 Laptop" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unit of Measure *</label>
                    <input type="text" name="unit" required placeholder="pcs, boxes, meters, kg" value="pcs" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Standard Unit Cost (Rs.) *</label>
                    <input type="number" step="0.01" name="unit_cost" required placeholder="0.00" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Reorder Alert Level *</label>
                    <input type="number" name="reorder_level" required value="10" min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>
            </div>

            <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-500/20 text-indigo-300 text-[11px] flex items-center space-x-2">
                <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Stock Control Notice:</strong> Master items are registered with <strong>0 opening stock</strong>. Stock can only be added to inventory via approved <strong>Stock Requests</strong>.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description / Technical Specifications</label>
                <textarea name="description" rows="2" placeholder="Hardware specs, material details..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Master Item -->
<div id="editItemModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
                <h3 class="font-bold text-white text-base">Edit Item</h3>
            </div>
            <button onclick="document.getElementById('editItemModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editItemForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- 4-Level Category Selectors -->
            <div class="p-4 bg-slate-950/80 border border-slate-800 rounded-2xl space-y-3">
                <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Category Levels (1 to 4)</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 1 * (Required)</label>
                        <select name="category_id" id="edit_cat_1" onchange="cascadeCategories('edit', 1, this.value)" required class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            @foreach($category1List as $c1)
                                <option value="{{ $c1->id }}">{{ $c1->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 2 (Optional)</label>
                        <select name="category_2_id" id="edit_cat_2" onchange="cascadeCategories('edit', 2, this.value)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- None --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 3 (Optional)</label>
                        <select name="category_3_id" id="edit_cat_3" onchange="cascadeCategories('edit', 3, this.value)" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- None --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category 4 (Optional)</label>
                        <select name="category_4_id" id="edit_cat_4" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">-- None --</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Product Specs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Product SKU / Code *</label>
                    <input type="text" name="sku" id="edit_sku" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white uppercase font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Product Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Unit of Measure *</label>
                    <input type="text" name="unit" id="edit_unit" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Standard Unit Cost (Rs.) *</label>
                    <input type="number" step="0.01" name="unit_cost" id="edit_unit_cost" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Reorder Alert Level *</label>
                    <input type="number" name="reorder_level" id="edit_reorder_level" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">On-Hand Stock (Read-Only)</label>
                    <div class="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs font-mono font-bold text-emerald-400" id="edit_stock_display">
                        0 pcs
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description / Technical Specifications</label>
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
    const allCat2 = @json($category2List);
    const allCat3 = @json($category3List);
    const allCat4 = @json($category4List);

    function cascadeCategories(prefix, changedLevel, selectedParentId) {
        selectedParentId = parseInt(selectedParentId);
        const isFilter = (prefix === 'filter');

        const cat2Default = isFilter ? 'All Category 2' : '-- None / Select Category 2 --';
        const cat3Default = isFilter ? 'All Category 3' : '-- None / Select Category 3 --';
        const cat4Default = isFilter ? 'All Category 4' : '-- None / Select Category 4 --';
        const emptyDefault = isFilter ? 'All Categories' : '-- None --';

        if (changedLevel === 1) {
            const cat2Select = document.getElementById(`${prefix}_cat_2`);
            const cat3Select = document.getElementById(`${prefix}_cat_3`);
            const cat4Select = document.getElementById(`${prefix}_cat_4`);

            if (cat2Select) cat2Select.innerHTML = `<option value="">${cat2Default}</option>`;
            if (cat3Select) cat3Select.innerHTML = `<option value="">${cat3Default}</option>`;
            if (cat4Select) cat4Select.innerHTML = `<option value="">${cat4Default}</option>`;

            if (cat2Select) {
                const filteredCat2 = isNaN(selectedParentId) ? allCat2 : allCat2.filter(c => c.parent_id === selectedParentId);
                filteredCat2.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    cat2Select.appendChild(opt);
                });
            }
        } else if (changedLevel === 2) {
            const cat3Select = document.getElementById(`${prefix}_cat_3`);
            const cat4Select = document.getElementById(`${prefix}_cat_4`);

            if (cat3Select) cat3Select.innerHTML = `<option value="">${cat3Default}</option>`;
            if (cat4Select) cat4Select.innerHTML = `<option value="">${cat4Default}</option>`;

            if (cat3Select) {
                const filteredCat3 = isNaN(selectedParentId) ? allCat3 : allCat3.filter(c => c.parent_id === selectedParentId);
                filteredCat3.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    cat3Select.appendChild(opt);
                });
            }
        } else if (changedLevel === 3) {
            const cat4Select = document.getElementById(`${prefix}_cat_4`);
            if (cat4Select) {
                cat4Select.innerHTML = `<option value="">${cat4Default}</option>`;
                const filteredCat4 = isNaN(selectedParentId) ? allCat4 : allCat4.filter(c => c.parent_id === selectedParentId);
                filteredCat4.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    cat4Select.appendChild(opt);
                });
            }
        }
    }

    function editItem(item) {
        document.getElementById('editItemForm').action = "{{ url('/inventory/items') }}/" + item.id;
        document.getElementById('edit_sku').value = item.sku;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_unit').value = item.unit;
        document.getElementById('edit_unit_cost').value = item.unit_cost;
        document.getElementById('edit_reorder_level').value = item.reorder_level;
        document.getElementById('edit_description').value = item.description || '';
        document.getElementById('edit_stock_display').textContent = (item.current_stock ?? 0) + ' ' + (item.unit ?? 'pcs');

        // Pre-populate Category 1..4 hierarchy
        if (item.category_id) {
            document.getElementById('edit_cat_1').value = item.category_id;
            cascadeCategories('edit', 1, item.category_id);

            if (item.category_2_id) {
                document.getElementById('edit_cat_2').value = item.category_2_id;
                cascadeCategories('edit', 2, item.category_2_id);

                if (item.category_3_id) {
                    document.getElementById('edit_cat_3').value = item.category_3_id;
                    cascadeCategories('edit', 3, item.category_3_id);

                    if (item.category_4_id) {
                        document.getElementById('edit_cat_4').value = item.category_4_id;
                    }
                }
            }
        }

        document.getElementById('editItemModal').classList.remove('hidden');
    }
</script>
@endsection
