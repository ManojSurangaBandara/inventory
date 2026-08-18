@extends('layouts.app')

@section('title', 'Suppliers Directory')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Suppliers & Vendors</h2>
            <p class="text-xs text-slate-400">Manage procurement vendor contacts and supply partner profiles for purchase orders.</p>
        </div>
        <button onclick="document.getElementById('addSupplierModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ New Supplier</span>
        </button>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">Supplier Name</th>
                        <th class="px-4 py-3.5">Email Contact</th>
                        <th class="px-4 py-3.5">Phone Number</th>
                        <th class="px-4 py-3.5">Business Address</th>
                        <th class="px-4 py-3.5">Purchase Orders</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($suppliers as $s)
                        @php
                            $hasDependencies = ($s->purchase_orders_count > 0);
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4">
                                <div class="font-bold text-white text-sm">{{ $s->name }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">ID: #{{ $s->id }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-300">
                                @if($s->email)
                                    <a href="mailto:{{ $s->email }}" class="text-indigo-400 hover:underline flex items-center gap-1">
                                        {{ $s->email }}
                                    </a>
                                @else
                                    <span class="text-slate-500 italic">No email</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-300">
                                {{ $s->phone ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-4 text-slate-400 max-w-xs truncate">
                                {{ $s->address ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-bold {{ $hasDependencies ? 'text-indigo-400' : 'text-slate-500' }}">
                                    {{ $s->purchase_orders_count }} {{ Str::plural('Order', $s->purchase_orders_count) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                @if(!$hasDependencies)
                                    <!-- Delete Supplier Button (Only shown for unused / deletable suppliers) -->
                                    <form action="{{ route('inventory.suppliers.destroy', $s->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete mistakenly created supplier \'{{ $s->name }}\'?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-lg transition" title="Delete unused supplier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[10px] text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 font-semibold inline-flex items-center gap-1" title="Active vendor with purchase order history">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                        Active Vendor
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No suppliers registered yet. Click "+ New Supplier" to add vendors.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
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
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email Contact</label>
                    <input type="email" name="email" placeholder="sales@vendor.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Phone Number</label>
                    <input type="text" name="phone" placeholder="+1 555 0199" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Business Address</label>
                <textarea name="address" rows="2" placeholder="e.g. 100 Industrial Parkway, Suite 200" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addSupplierModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700 font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Supplier</button>
            </div>
        </form>
    </div>
</div>
@endsection
