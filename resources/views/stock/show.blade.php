@extends('layouts.app')

@section('title', 'Stock Request & Approval Audit')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('stock.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">&larr; Back to Stock Requests</a>
            <h2 class="text-xl font-bold text-white flex items-center gap-3 flex-wrap">
                <span>Request #{{ $movement->reference_code }}</span>
                @if($movement->items->count() > 1)
                    <span class="px-2.5 py-1 text-xs font-mono bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 rounded-lg">
                        {{ $movement->items->count() }} Line Items (Total {{ $movement->total_quantity }} pcs)
                    </span>
                @else
                    <span class="px-2.5 py-1 text-xs font-mono bg-amber-500/10 text-amber-300 border border-amber-500/30 rounded-lg">
                        Lot: {{ $movement->item_lot_number ?? 'LOT-N/A' }}
                    </span>
                @endif
            </h2>
        </div>

        <span class="px-4 py-1.5 rounded-full text-xs font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
            Current Stage: {{ $movement->current_state }}
        </span>
    </div>

    <!-- Rejection Alert Banner if rejected -->
    @if($movement->rejection_reason || str_contains(strtolower($movement->current_state), 'reject'))
        <div class="bg-rose-950/40 border border-rose-500/30 p-5 rounded-2xl text-rose-200">
            <h3 class="font-bold text-rose-100 mb-1 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Request Rejected during Approval Process
            </h3>
            <p class="text-xs text-rose-300">Rejection Reason: <strong class="text-white font-mono bg-rose-900/60 px-2 py-0.5 rounded">{{ $movement->rejection_reason ?? 'Information incorrect or missing requirements.' }}</strong></p>
        </div>
    @endif

    <!-- Movement Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Requisition Type & Warehouse</span>
            <div class="font-bold text-white text-base">
                @if($movement->type === 'inbound')
                    <span class="text-emerald-400">Add to Main Stock (Inbound)</span>
                @else
                    <span class="text-amber-400">Item Issue Requisition (Outbound)</span>
                @endif
            </div>
            <div class="text-xs text-slate-300">Warehouse: <strong class="text-white">{{ $movement->warehouse->name ?? 'N/A' }}</strong></div>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Source System & Origin</span>
            <div class="text-xs text-slate-300">
                Source: 
                @if($movement->source_system === 'workshop_api')
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded">Workshop API</span>
                @else
                    <span class="px-2 py-0.5 text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700 rounded">Manual Entry</span>
                @endif
            </div>
            <div class="text-xs text-slate-300">Initiated By: <strong class="text-white">{{ $movement->creator->name ?? 'Workshop Management System (API)' }}</strong></div>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Audit Meta</span>
            <div class="text-xs text-slate-400">Created: <strong class="text-slate-200">{{ $movement->created_at->format('M d, Y H:i:s') }}</strong></div>
            <div class="text-xs text-slate-400">Notes: {{ $movement->notes ?? 'No notes provided.' }}</div>
        </div>
    </div>

    <!-- Requisition Multi-Item Lot Details Table -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-white text-base">Requisition Line Items & Lots</h3>
            <span class="text-xs text-slate-400">Total Items: <strong class="text-white">{{ $movement->items->count() > 0 ? $movement->items->count() : 1 }}</strong></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Item Name & Details</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Item Lot Number</th>
                        <th class="px-4 py-3 text-right">Requested Qty</th>
                        <th class="px-4 py-3 text-right">Current Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($movement->items as $itemRow)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3.5 font-bold text-white">
                                {{ $itemRow->item->name ?? 'Deleted Item' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-indigo-400">
                                {{ $itemRow->item->sku ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-amber-300 font-semibold">
                                {{ $itemRow->item_lot_number ?? 'LOT-N/A' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-white">
                                {{ $itemRow->quantity }} {{ $itemRow->item->unit ?? 'pcs' }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-slate-400 font-mono">
                                {{ $itemRow->item->current_stock ?? '0' }} {{ $itemRow->item->unit ?? 'pcs' }}
                            </td>
                        </tr>
                    @empty
                        <!-- Fallback single item display if legacy record -->
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3.5 font-bold text-white">
                                {{ $movement->item->name ?? 'Deleted Item' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-indigo-400">
                                {{ $movement->item->sku ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3.5 font-mono text-amber-300 font-semibold">
                                {{ $movement->item_lot_number ?? 'LOT-N/A' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-white">
                                {{ $movement->quantity }} {{ $movement->item->unit ?? 'pcs' }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-slate-400 font-mono">
                                {{ $movement->item->current_stock ?? '0' }} {{ $movement->item->unit ?? 'pcs' }}
                            </td>
                        </tr>
                    @endempty
                </tbody>
            </table>
        </div>
    </div>

    <!-- Available Approval Actions for Current User -->
    @if(count($availableTransitions) > 0)
        <div class="bg-indigo-950/30 border border-indigo-500/30 rounded-3xl p-5 shadow-xl">
            <h3 class="font-bold text-indigo-100 text-sm mb-3">Execute Next Approval Action:</h3>
            <div class="flex items-center gap-3 flex-wrap">
                @foreach($availableTransitions as $tr)
                    <button onclick="triggerTransition({{ $movement->id }}, {{ $tr['transition_id'] }}, '{{ $tr['action_name'] }}', {{ $tr['requires_note'] ? 'true' : 'false' }})"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg transition">
                        {{ $tr['action_name'] }} &rarr;
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Workflow Audit Log History Timeline -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="font-bold text-white text-base">Configured Workflow Audit Trail</h3>

        <div class="relative pl-6 border-l-2 border-slate-800 space-y-6">
            @forelse($logs as $log)
                <div class="relative group">
                    <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-indigo-600 border-4 border-slate-900"></div>

                    <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-white text-xs">{{ $log->action }}</span>
                            <span class="text-[10px] text-slate-400">{{ $log->created_at->format('M d, Y H:i:s') }}</span>
                        </div>

                        <div class="text-xs text-slate-300">
                            Stage Transition: 
                            <span class="font-mono text-slate-400">{{ $log->from_state ?? 'Requisition' }}</span> 
                            &rarr; 
                            <span class="font-mono text-indigo-400 font-bold">{{ $log->to_state }}</span>
                        </div>

                        <div class="text-xs text-slate-400">
                            Action Performed By: <strong class="text-slate-200">{{ $log->user->name ?? 'Automated Pipeline / System' }}</strong>
                        </div>

                        @if($log->notes)
                            <div class="mt-2 text-xs bg-slate-900 p-2 rounded-xl text-slate-300 font-mono border border-slate-800">
                                Note: {{ $log->notes }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-500 italic">No transition audit logs recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal: Execute Workflow Transition -->
<div id="transitionModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Workflow Action Confirmation</h3>
            <button onclick="document.getElementById('transitionModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="transitionForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="transition_id" id="tr_transition_id">

            <p class="text-xs text-slate-300">
                Are you sure you want to perform: <strong id="tr_action_name" class="text-indigo-400 font-bold"></strong>?
            </p>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">
                    Approval / Rejection Audit Note <span id="note_required_indicator" class="text-rose-400 hidden">*</span>
                </label>
                <textarea name="notes" id="tr_notes" rows="3" placeholder="Provide reason or audit notes for this action..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('transitionModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Confirm Action</button>
            </div>
        </form>
    </div>
</div>

<script>
    function triggerTransition(movementId, transitionId, actionName, requiresNote) {
        document.getElementById('transitionForm').action = "{{ url('/stock') }}/" + movementId + "/transition";
        document.getElementById('tr_transition_id').value = transitionId;
        document.getElementById('tr_action_name').textContent = actionName;
        document.getElementById('tr_notes').value = '';

        const noteReq = document.getElementById('note_required_indicator');
        const notesField = document.getElementById('tr_notes');
        if (requiresNote) {
            noteReq.classList.remove('hidden');
            notesField.required = true;
        } else {
            noteReq.classList.add('hidden');
            notesField.required = false;
        }

        document.getElementById('transitionModal').classList.remove('hidden');
    }
</script>
@endsection
