@extends('layouts.app')

@section('title', 'Category Master Data (Categories 1 - 4)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">4-Level Category Master Data</h2>
            <p class="text-xs text-slate-400">Manage 4-tier category classification: Category 1 (Required), Category 2 (Optional), Category 3 (Optional), and Category 4 (Optional).</p>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="openAddModal(1)" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Add Category</span>
            </button>
        </div>
    </div>

    <!-- Category Level Navigation Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-800 pb-3 overflow-x-auto text-xs font-semibold">
        <button onclick="switchTab('tab-c1', this)" class="tab-btn px-4 py-2 rounded-xl bg-indigo-600 text-white shadow-lg transition">
            Category 1 ({{ $cat1List->count() }})
        </button>
        <button onclick="switchTab('tab-c2', this)" class="tab-btn px-4 py-2 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white transition">
            Category 2 ({{ $cat2List->count() }})
        </button>
        <button onclick="switchTab('tab-c3', this)" class="tab-btn px-4 py-2 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white transition">
            Category 3 ({{ $cat3List->count() }})
        </button>
        <button onclick="switchTab('tab-c4', this)" class="tab-btn px-4 py-2 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white transition">
            Category 4 ({{ $cat4List->count() }})
        </button>
    </div>

    <!-- TAB 1: Category 1 -->
    <div id="tab-c1" class="tab-content space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-xs text-slate-400">
                <strong class="text-white">Category 1:</strong> Primary category required when creating items in master data.
            </div>
            <button onclick="openAddModal(1)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-slate-700 rounded-lg text-xs font-semibold">
                + Add Category 1
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($cat1List as $c1)
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3 hover:border-indigo-500/30 transition">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold uppercase">
                                Category 1
                            </span>
                            <div class="flex items-center space-x-1">
                                <button onclick='openEditModal({{ json_encode($c1) }})' class="text-slate-400 hover:text-amber-400 p-1 text-xs">Edit</button>
                                <form action="{{ route('inventory.categories.destroy', $c1->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete Category 1 \'{{ $c1->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-500 hover:text-rose-400 p-1 text-xs">Del</button>
                                </form>
                            </div>
                        </div>
                        <h4 class="font-bold text-white text-base">{{ $c1->name }}</h4>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ $c1->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-800 text-[11px] text-slate-400">
                        <span>Child Categories: <strong class="text-white">{{ $c1->children_count }}</strong></span>
                        <span>Items Tagged: <strong class="text-emerald-400">{{ $c1->items_count }}</strong></span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                    No Category 1 records defined. Click "+ Add Category 1" to start.
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 2: Category 2 -->
    <div id="tab-c2" class="tab-content hidden space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-xs text-slate-400">
                <strong class="text-white">Category 2 (Optional):</strong> Secondary level category linked to a Category 1 parent.
            </div>
            <button onclick="openAddModal(2)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-slate-700 rounded-lg text-xs font-semibold">
                + Add Category 2
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($cat2List as $c2)
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3 hover:border-indigo-500/30 transition">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] bg-purple-500/10 text-purple-400 border border-purple-500/20 font-bold uppercase">
                                Category 2
                            </span>
                            <div class="flex items-center space-x-1">
                                <button onclick='openEditModal({{ json_encode($c2) }})' class="text-slate-400 hover:text-amber-400 p-1 text-xs">Edit</button>
                                <form action="{{ route('inventory.categories.destroy', $c2->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete Category 2 \'{{ $c2->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-500 hover:text-rose-400 p-1 text-xs">Del</button>
                                </form>
                            </div>
                        </div>

                        <div class="text-[10px] text-indigo-400 font-semibold">
                            Parent Category 1: <strong class="text-white">{{ $c2->parent->name ?? 'None' }}</strong>
                        </div>

                        <h4 class="font-bold text-white text-base">{{ $c2->name }}</h4>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ $c2->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-800 text-[11px] text-slate-400">
                        <span>Child Categories: <strong class="text-white">{{ $c2->children_count }}</strong></span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                    No Category 2 records defined.
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 3: Category 3 -->
    <div id="tab-c3" class="tab-content hidden space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-xs text-slate-400">
                <strong class="text-white">Category 3 (Optional):</strong> Tertiary level category linked to a Category 2 parent.
            </div>
            <button onclick="openAddModal(3)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-slate-700 rounded-lg text-xs font-semibold">
                + Add Category 3
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($cat3List as $c3)
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3 hover:border-indigo-500/30 transition">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold uppercase">
                                Category 3
                            </span>
                            <div class="flex items-center space-x-1">
                                <button onclick='openEditModal({{ json_encode($c3) }})' class="text-slate-400 hover:text-amber-400 p-1 text-xs">Edit</button>
                                <form action="{{ route('inventory.categories.destroy', $c3->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete Category 3 \'{{ $c3->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-500 hover:text-rose-400 p-1 text-xs">Del</button>
                                </form>
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-400 space-y-0.5">
                            <div>Parent Category 2: <strong class="text-white">{{ $c3->parent->name ?? 'N/A' }}</strong></div>
                        </div>

                        <h4 class="font-bold text-white text-base">{{ $c3->name }}</h4>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ $c3->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-800 text-[11px] text-slate-400">
                        <span>Child Categories: <strong class="text-white">{{ $c3->children_count }}</strong></span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                    No Category 3 records defined.
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 4: Category 4 -->
    <div id="tab-c4" class="tab-content hidden space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-xs text-slate-400">
                <strong class="text-white">Category 4 (Optional):</strong> Deepest 4th level category linked to a Category 3 parent.
            </div>
            <button onclick="openAddModal(4)" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-slate-700 rounded-lg text-xs font-semibold">
                + Add Category 4
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($cat4List as $c4)
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3 hover:border-indigo-500/30 transition">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold uppercase">
                                Category 4
                            </span>
                            <div class="flex items-center space-x-1">
                                <button onclick='openEditModal({{ json_encode($c4) }})' class="text-slate-400 hover:text-amber-400 p-1 text-xs">Edit</button>
                                <form action="{{ route('inventory.categories.destroy', $c4->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete Category 4 \'{{ $c4->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-500 hover:text-rose-400 p-1 text-xs">Del</button>
                                </form>
                            </div>
                        </div>

                        <div class="text-[10px] text-slate-400">
                            Parent Category 3: <strong class="text-white">{{ $c4->parent->name ?? 'N/A' }}</strong>
                        </div>

                        <h4 class="font-bold text-white text-base">{{ $c4->name }}</h4>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ $c4->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="pt-3 border-t border-slate-800 text-[10px] font-mono text-slate-400 truncate">
                        Path: {{ $c4->full_path }}
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                    No Category 4 records defined.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal: Add Category -->
<div id="addCategoryModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 id="addModalTitle" class="font-bold text-white text-base">Add Category</h3>
            <button onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('inventory.categories.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Category Level *</label>
                <select name="level" id="add_cat_level" onchange="updateParentOptions(this.value)" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="1">Category 1 (Primary / Required)</option>
                    <option value="2">Category 2 (Optional)</option>
                    <option value="3">Category 3 (Optional)</option>
                    <option value="4">Category 4 (Optional)</option>
                </select>
            </div>

            <div id="parentSelectorGroup" class="hidden">
                <label id="parentLabel" class="block text-xs font-semibold text-slate-300 mb-1">Parent Category *</label>
                <select name="parent_id" id="add_cat_parent" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <!-- Populated dynamically -->
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                <input type="text" name="name" required placeholder="e.g. Electronics & Computing" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Category details..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Category -->
<div id="editCategoryModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Category</h3>
            <button onclick="document.getElementById('editCategoryModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editCategoryForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                <input type="text" name="name" id="edit_cat_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" id="edit_cat_description" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    const cat1Data = @json($cat1List);
    const cat2Data = @json($cat2List);
    const cat3Data = @json($cat3List);

    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg');
            el.classList.add('bg-slate-800/80', 'text-slate-400');
        });

        document.getElementById(tabId).classList.remove('hidden');
        btn.classList.remove('bg-slate-800/80', 'text-slate-400');
        btn.classList.add('bg-indigo-600', 'text-white', 'shadow-lg');
    }

    function openAddModal(level = 1) {
        document.getElementById('add_cat_level').value = level;
        updateParentOptions(level);
        document.getElementById('addCategoryModal').classList.remove('hidden');
    }

    function updateParentOptions(level) {
        const parentGroup = document.getElementById('parentSelectorGroup');
        const parentSelect = document.getElementById('add_cat_parent');
        const parentLabel = document.getElementById('parentLabel');
        parentSelect.innerHTML = '';

        if (parseInt(level) === 1) {
            parentGroup.classList.add('hidden');
            parentSelect.required = false;
        } else if (parseInt(level) === 2) {
            parentGroup.classList.remove('hidden');
            parentLabel.textContent = 'Parent Category 1 *';
            parentSelect.required = true;
            cat1Data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                parentSelect.appendChild(opt);
            });
        } else if (parseInt(level) === 3) {
            parentGroup.classList.remove('hidden');
            parentLabel.textContent = 'Parent Category 2 *';
            parentSelect.required = true;
            cat2Data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = `${item.name} (under ${item.parent ? item.parent.name : 'Cat 1'})`;
                parentSelect.appendChild(opt);
            });
        } else if (parseInt(level) === 4) {
            parentGroup.classList.remove('hidden');
            parentLabel.textContent = 'Parent Category 3 *';
            parentSelect.required = true;
            cat3Data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = `${item.name} (under ${item.parent ? item.parent.name : 'Cat 2'})`;
                parentSelect.appendChild(opt);
            });
        }
    }

    function openEditModal(cat) {
        document.getElementById('editCategoryForm').action = "{{ url('/inventory/categories') }}/" + cat.id;
        document.getElementById('edit_cat_name').value = cat.name;
        document.getElementById('edit_cat_description').value = cat.description || '';
        document.getElementById('editCategoryModal').classList.remove('hidden');
    }
</script>
@endsection
