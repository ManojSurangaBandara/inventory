@extends('layouts.app')

@section('title', 'Stock Requests & Issues')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Stock Requests & Issue Pipeline</h2>
            <p class="text-xs text-slate-400">Manage inbound main stock lot additions and Workshop Management System item issue requests.</p>
        </div>
        <button onclick="document.getElementById('addMovementModal').classList.remove('hidden')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create Stock Request</span>
        </button>
    </div>

    <!-- Active Workflow Banner Info -->
    @if($workflow)
        <div class="bg-indigo-950/30 border border-indigo-500/30 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold text-xs">
                    WF
                </div>
                <div>
                    <h4 class="text-xs font-bold text-white">Active Workflow Engine: <span class="text-indigo-400">{{ $workflow->name }}</span></h4>
                    <p class="text-[10px] text-slate-400">Configured Multi-level approval path (Subject Clerk / API &rarr; OC &rarr; QM &rarr; CO &rarr; Storemen).</p>
                </div>
            </div>
            <a href="{{ route('workflows.builder', $workflow->id) }}" class="text-xs text-indigo-400 hover:underline font-semibold">Configure Approval Flow &rarr;</a>
        </div>
    @endif

    <!-- Stock Movements Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3.5">Ref Code & Lot</th>
                        <th class="px-4 py-3.5">Source & Type</th>
                        <th class="px-4 py-3.5">Item & Qty</th>
                        <th class="px-4 py-3.5">Warehouse</th>
                        <th class="px-4 py-3.5">Approval Stage</th>
                        <th class="px-4 py-3.5 text-right">Execute Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($movements as $m)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-4 py-4 font-mono font-bold">
                                <a href="{{ route('stock.show', $m->id) }}" class="text-indigo-400 hover:underline flex items-center space-x-1">
                                    <span>{{ $m->reference_code }}</span>
                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <div class="text-[10px] text-slate-400 font-normal">Lot: <span class="text-amber-300 font-semibold">{{ $m->item_lot_number ?? 'LOT-N/A' }}</span></div>
                            </td>
                            <td class="px-4 py-4 uppercase font-semibold text-[10px] space-y-1">
                                <div>
                                    @if($m->source_system === 'workshop_api')
                                        <span class="px-2 py-0.5 rounded border border-purple-500/30 bg-purple-500/10 text-purple-300">Workshop API</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded border border-slate-700 bg-slate-800 text-slate-400">Manual Entry</span>
                                    @endif
                                </div>
                                <div>
                                    @if($m->type === 'inbound')
                                        <span class="text-emerald-400 font-bold">Add to Main Stock</span>
                                    @else
                                        <span class="text-amber-400 font-bold">Item Issue Request</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-white">{{ $m->item->name ?? 'Deleted Item' }}</div>
                                <div class="text-[10px] text-slate-400 font-bold">Qty: {{ $m->quantity }} {{ $m->item->unit ?? 'pcs' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-300">
                                {{ $m->warehouse->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-4">
                                @php
                                    $st = $stateDetailsMap[$m->current_state] ?? null;
                                    $badgeColor = $st ? [
                                        'emerald' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30',
                                        'amber' => 'bg-amber-500/10 text-amber-300 border-amber-500/30',
                                        'indigo' => 'bg-indigo-500/10 text-indigo-300 border-indigo-500/30',
                                        'rose' => 'bg-rose-500/10 text-rose-300 border-rose-500/30',
                                        'purple' => 'bg-purple-500/10 text-purple-300 border-purple-500/30',
                                    ][$st->color] ?? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-slate-800 text-slate-300 border-slate-700';
                                @endphp

                                <span class="px-3 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider {{ $badgeColor }}">
                                    {{ $st ? $st->name : $m->current_state }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                @php
                                    $transitions = $availableTransitionsMap[$m->id] ?? [];
                                @endphp

                                @if(count($transitions) > 0)
                                    <div class="flex items-center justify-end space-x-1">
                                        @foreach($transitions as $tr)
                                            <button onclick="triggerTransition({{ $m->id }}, {{ $tr['transition_id'] }}, '{{ $tr['action_name'] }}', {{ $tr['requires_note'] ? 'true' : 'false' }})"
                                                    class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-semibold text-[11px] transition shadow">
                                                {{ $tr['action_name'] }} &rarr;
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <a href="{{ route('stock.show', $m->id) }}" class="text-[10px] text-indigo-400 hover:underline font-medium">View Audit History</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No stock requests or issue movements recorded.</td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Initiate Stock Request -->
<div id="addMovementModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Create Stock Requisition (Subject Clerk)</h3>
            <button onclick="document.getElementById('addMovementModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('stock.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Process Type *</label>
                    <select name="type" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="inbound">Add items to main stock (Inbound Lot)</option>
                        <option value="outbound">Item Request Process (Outbound Issue)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Inventory Product *</label>
                    <select name="inventory_item_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->sku }} - {{ $item->name }} (Stock: {{ $item->current_stock }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Item Lot Number</label>
                    <input type="text" name="item_lot_number" placeholder="e.g. LOT-2026-001" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Quantity *</label>
                    <input type="number" name="quantity" required min="1" value="10" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Target Warehouse *</label>
                <select name="warehouse_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Requisition Notes</label>
                <textarea name="notes" rows="2" placeholder="Item lot specifications or requisition rationale..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addMovementModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Submit Requisition</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Execute Transition Action -->
<div id="transitionModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Execute Approval Action</h3>
            <button onclick="document.getElementById('transitionModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="transitionForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="transition_id" id="tr_transition_id">

            <div class="bg-indigo-950/30 border border-indigo-500/30 p-3 rounded-2xl">
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block">Approval Action:</span>
                <span class="font-bold text-indigo-300 text-sm block" id="tr_action_name"></span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Approval Note / Rejection Reason <span id="note_required_span" class="text-rose-400 hidden">*</span></label>
                <textarea name="notes" id="tr_notes" rows="3" placeholder="Enter quality verification summary or rejection reason..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('transitionModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Confirm & Execute</button>
            </div>
        </form>
    </div>
</div>

<script>
    function triggerTransition(movementId, transitionId, actionName, requiresNote) {
        document.getElementById('transitionForm').action = "/stock/" + movementId + "/transition";
        document.getElementById('tr_transition_id').value = transitionId;
        document.getElementById('tr_action_name').textContent = actionName;
        document.getElementById('tr_notes').value = '';

        if (requiresNote) {
            document.getElementById('note_required_span').classList.remove('hidden');
            document.getElementById('tr_notes').required = true;
        } else {
            document.getElementById('note_required_span').classList.add('hidden');
            document.getElementById('tr_notes').required = false;
        }

        document.getElementById('transitionModal').classList.remove('hidden');
    }
</script>
@endsection
