@extends('layouts.app')

@section('title', 'Item Categories')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Categories Master</h2>
            <p class="text-xs text-slate-400">Classify inventory products across tenant stock.</p>
        </div>
        <button onclick="document.getElementById('addCatModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition">
            + New Category
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @forelse($categories as $cat)
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-white text-base">{{ $cat->name }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] bg-indigo-500/10 text-indigo-300 font-bold border border-indigo-500/20">
                        {{ $cat->items_count }} {{ Str::plural('Item', $cat->items_count) }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 min-h-[32px]">{{ $cat->description ?? 'No description provided.' }}</p>
            </div>
        @empty
            <div class="col-span-full text-center text-slate-500 py-8">No categories added yet.</div>
        @endempty
    </div>
</div>

<!-- Modal: Add Category -->
<div id="addCatModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add Item Category</h3>
            <button onclick="document.getElementById('addCatModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('inventory.categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Category Name *</label>
                <input type="text" name="name" required placeholder="e.g. Computer Hardware" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addCatModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
