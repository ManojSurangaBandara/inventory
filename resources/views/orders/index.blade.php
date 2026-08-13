@extends('layouts.app')

@section('title', 'Purchase Orders & Approvals')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Purchase Orders</h2>
            <p class="text-xs text-slate-400">Manage supplier procurement orders and workflow approval stages.</p>
        </div>
        <button onclick="document.getElementById('addPOModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create Purchase Order</span>
        </button>
    </div>

    <!-- PO Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">PO Number</th>
                        <th class="px-4 py-3.5">Supplier</th>
                        <th class="px-4 py-3.5">Warehouse</th>
                        <th class="px-4 py-3.5">Total Amount</th>
                        <th class="px-4 py-3.5">Workflow State</th>
                        <th class="px-4 py-3.5 text-right">Execute Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($orders as $po)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4 font-mono font-bold text-indigo-400">
                                <a href="{{ route('orders.show', $po->id) }}" class="hover:underline flex items-center space-x-1">
                                    <span>{{ $po->po_number }}</span>
                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>
                            <td class="px-4 py-4 font-bold text-white">{{ $po->supplier->name ?? 'N/A' }}</td>
                            <td class="px-4 py-4 text-slate-300">{{ $po->warehouse->name ?? 'N/A' }}</td>
                            <td class="px-4 py-4 font-bold text-emerald-400 text-sm">${{ number_format($po->total_amount, 2) }}</td>
                            <td class="px-4 py-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                                    {{ $po->current_state }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                @php
                                    $transitions = $availableTransitionsMap[$po->id] ?? [];
                                @endphp

                                @if(count($transitions) > 0)
                                    <div class="flex items-center justify-end space-x-1">
                                        @foreach($transitions as $tr)
                                            <button onclick="triggerPOTransition({{ $po->id }}, {{ $tr['transition_id'] }}, '{{ $tr['action_name'] }}', {{ $tr['requires_note'] ? 'true' : 'false' }})"
                                                    class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-semibold text-[11px] transition shadow">
                                                {{ $tr['action_name'] }} &rarr;
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-500 italic">No actions available</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No purchase orders created yet.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create Purchase Order -->
<div id="addPOModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-xl shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Create Purchase Order</h3>
            <button onclick="document.getElementById('addPOModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('orders.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Select Supplier *</label>
                    <select name="supplier_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Destination Warehouse *</label>
                    <select name="warehouse_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Order Items -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-indigo-400 uppercase tracking-wider">Order Line Item</label>
                <div class="grid grid-cols-3 gap-2 bg-slate-950 p-3 border border-slate-800 rounded-xl">
                    <div class="col-span-1">
                        <label class="block text-[10px] text-slate-400 mb-1">Product</label>
                        <select name="items[0][inventory_item_id]" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white">
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1">Quantity</label>
                        <input type="number" name="items[0][quantity]" required min="1" value="20" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white">
                    </div>

                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1">Unit Price ($)</label>
                        <input type="number" step="0.01" name="items[0][unit_price]" required value="150.00" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Purchase Order Notes</label>
                <textarea name="notes" rows="2" placeholder="Delivery instructions or payment terms..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addPOModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Submit Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Execute PO Transition Action -->
<div id="poTransitionModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Execute PO Workflow Action</h3>
            <button onclick="document.getElementById('poTransitionModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="poTransitionForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="transition_id" id="po_tr_transition_id">

            <div class="bg-indigo-950/30 border border-indigo-500/30 p-3 rounded-2xl">
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block">Action to execute:</span>
                <span class="font-bold text-indigo-300 text-sm block" id="po_tr_action_name"></span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Audit Log Note / Reason <span id="po_note_required_span" class="text-rose-400 hidden">*</span></label>
                <textarea name="notes" id="po_tr_notes" rows="3" placeholder="Enter reason or approval comments..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('poTransitionModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Confirm Action</button>
            </div>
        </form>
    </div>
</div>

<script>
    function triggerPOTransition(poId, transitionId, actionName, requiresNote) {
        document.getElementById('poTransitionForm').action = "{{ url('/orders') }}/" + poId + "/transition";
        document.getElementById('po_tr_transition_id').value = transitionId;
        document.getElementById('po_tr_action_name').textContent = actionName;
        document.getElementById('po_tr_notes').value = '';

        if (requiresNote) {
            document.getElementById('po_note_required_span').classList.remove('hidden');
            document.getElementById('po_tr_notes').required = true;
        } else {
            document.getElementById('po_note_required_span').classList.add('hidden');
            document.getElementById('po_tr_notes').required = false;
        }

        document.getElementById('poTransitionModal').classList.remove('hidden');
    }
</script>
@endsection
