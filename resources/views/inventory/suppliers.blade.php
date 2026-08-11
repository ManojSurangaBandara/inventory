@extends('layouts.app')

@section('title', 'Suppliers Directory')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Suppliers & Vendors</h2>
            <p class="text-xs text-slate-400">Manage procurement vendor contacts for purchase orders.</p>
        </div>
        <button onclick="document.getElementById('addSupplierModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition">
            + New Supplier
        </button>
    </div>

    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-4 py-3">Supplier Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Purchase Orders</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($suppliers as $s)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="px-4 py-3 font-bold text-white">{{ $s->name }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $s->email ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $s->phone ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $s->address ?? 'N/A' }}</td>
                        <td class="px-4 py-3 font-bold text-indigo-400">{{ $s->purchase_orders_count }} POs</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No suppliers registered yet.</td>
                    </tr>
                @endempty
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Supplier -->
<div id="addSupplierModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add New Supplier</h3>
            <button onclick="document.getElementById('addSupplierModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('inventory.suppliers.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Company / Vendor Name *</label>
                <input type="text" name="name" required placeholder="e.g. Global Tech Distributors" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email</label>
                    <input type="email" name="email" placeholder="sales@vendor.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Phone</label>
                    <input type="text" name="phone" placeholder="+1 555 0199" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Address</label>
                <textarea name="address" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addSupplierModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Supplier</button>
            </div>
        </form>
    </div>
</div>
@endsection
