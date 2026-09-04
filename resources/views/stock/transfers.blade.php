@extends('layouts.app')

@section('title', 'Inter-Warehouse Stock Transfers')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Inter-Warehouse Transfers</h2>
            <p class="text-xs text-slate-400">Manage cross-depot stock transfers, multi-item transit shipments, and workflow clearance.</p>
        </div>
        <button onclick="document.getElementById('addTransferModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Initiate Stock Transfer</span>
        </button>
    </div>

    <!-- Active Workflow Banner Info -->
    @if($workflow)
        <div class="bg-indigo-950/30 border border-indigo-500/30 rounded-2xl p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold text-xs">
                    WF
                </div>
                <div>
                    <h4 class="font-bold text-white text-xs">Active Transfer Pattern: {{ $workflow->name }}</h4>
                    <p class="text-[11px] text-slate-400">Strict state machine progression enforced on all inter-depot movements.</p>
                </div>
            </div>
            @if(Auth::user()->is_org_admin || Auth::user()->is_super_admin)
                <a href="{{ route('workflows.builder', $workflow->id) }}" class="text-xs text-indigo-400 hover:underline flex items-center space-x-1 font-semibold">
                    <span>Configure Transfer Pattern</span>
                    <span>&rarr;</span>
                </a>
            @endif
        </div>
    @endif

    <!-- Transfers Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">Reference Code</th>
                        <th class="px-4 py-3.5">Transfer Route</th>
                        <th class="px-4 py-3.5">Items & Quantity</th>
                        <th class="px-4 py-3.5">Created By</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($transfers as $t)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-3.5 font-mono font-bold text-indigo-400">
                                <a href="{{ route('stock.show', $t->id) }}" class="hover:underline">{{ $t->reference_code }}</a>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center space-x-1.5 font-semibold text-white">
                                    <span class="px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-slate-300">{{ $t->warehouse->name ?? 'Origin' }}</span>
                                    <span class="text-indigo-400">&rarr;</span>
                                    <span class="px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-indigo-300">{{ $t->targetWarehouse->name ?? 'Destination' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($t->items->count() > 1)
                                    <span class="font-bold text-white">{{ $t->items->count() }} Line Items</span>
                                    <span class="text-[10px] text-slate-400 block font-mono">({{ $t->total_quantity }} units)</span>
                                @elseif($t->items->count() === 1)
                                    @php $firstItem = $t->items->first(); @endphp
                                    <span class="font-bold text-white">{{ $firstItem->item->name ?? 'Item' }}</span>
                                    <span class="text-[10px] text-slate-400 block">Qty: {{ $firstItem->quantity }} {{ $firstItem->item->unit ?? 'units' }}</span>
                                @else
                                    <span class="font-bold text-white">{{ $t->item->name ?? 'Item' }}</span>
                                    <span class="text-[10px] text-slate-400 block">Qty: {{ $t->quantity }} {{ $t->item->unit ?? 'units' }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-400">
                                {{ $t->creator->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-full text-[10px] bg-slate-800 text-indigo-300 border border-indigo-500/30 font-semibold uppercase">
                                    {{ $t->current_state }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('stock.show', $t->id) }}" class="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold transition">
                                    Track / Progress &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No stock transfers recorded yet. Click "Initiate Stock Transfer" to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Initiate Stock Transfer -->
<div id="addTransferModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Initiate Inter-Warehouse Transfer</h3>
            <button onclick="document.getElementById('addTransferModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('stock.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="transfer">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Source Origin Warehouse *</label>
                    <select name="warehouse_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">-- Select Source Depot --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Target Destination Warehouse *</label>
                    <select name="target_warehouse_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">-- Select Target Depot --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Single or Multi-Item Line Rows -->
            <div class="space-y-3 border-t border-slate-800 pt-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-300">Transfer Item Lots *</label>
                    <button type="button" onclick="addTransferItemRow()" class="text-xs text-indigo-400 hover:underline font-bold">+ Add Another Item</button>
                </div>

                <div id="transferItemsContainer" class="space-y-2">
                    <div class="transfer-row grid grid-cols-12 gap-2 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 items-center">
                        <div class="col-span-6">
                            <select name="items[0][inventory_item_id]" required class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white">
                                <option value="">-- Select Item --</option>
                                @foreach($items as $it)
                                    <option value="{{ $it->id }}">{{ $it->sku }} - {{ $it->name }} ({{ $it->current_stock }} {{ $it->unit }} avail)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="number" name="items[0][quantity]" min="1" required placeholder="Quantity" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white">
                        </div>
                        <div class="col-span-3">
                            <input type="text" name="items[0][lot_number]" placeholder="Lot / Serial #" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Transfer Shipment Notes</label>
                <textarea name="notes" rows="2" placeholder="Shipment carrier, vehicle number, or special instructions..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addTransferModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition">Submit Transfer Request</button>
            </div>
        </form>
    </div>
</div>

<script>
    let transferRowIndex = 1;
    function addTransferItemRow() {
        const container = document.getElementById('transferItemsContainer');
        const row = document.createElement('div');
        row.className = 'transfer-row grid grid-cols-12 gap-2 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 items-center';
        row.innerHTML = `
            <div class="col-span-6">
                <select name="items[${transferRowIndex}][inventory_item_id]" required class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white">
                    <option value="">-- Select Item --</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}">{{ $it->sku }} - {{ $it->name }} ({{ $it->current_stock }} {{ $it->unit }} avail)</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-3">
                <input type="number" name="items[${transferRowIndex}][quantity]" min="1" required placeholder="Quantity" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white">
            </div>
            <div class="col-span-2">
                <input type="text" name="items[${transferRowIndex}][lot_number]" placeholder="Lot #" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white">
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="this.closest('.transfer-row').remove()" class="text-rose-400 hover:text-rose-300 text-base font-bold">&times;</button>
            </div>
        `;
        container.appendChild(row);
        transferRowIndex++;
    }
</script>
@endsection
