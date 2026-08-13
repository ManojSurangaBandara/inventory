@extends('layouts.app')

@section('title', 'Workflow Canvas Builder')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<div class="space-y-6">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <a href="{{ route('workflows.index') }}" class="text-xs text-indigo-400 hover:underline">&larr; Back to Workflows</a>
                <span class="text-slate-600">•</span>
                <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">{{ $workflow->entity_type }} Module</span>
            </div>
            <h2 class="text-xl font-bold text-white">{{ $workflow->name }}</h2>
            <p class="text-xs text-slate-400">{{ $workflow->description ?? 'Configure states and action rules visually.' }}</p>
        </div>

        <div class="flex items-center space-x-3">
            <button onclick="document.getElementById('addStateModal').classList.remove('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-xl text-xs border border-slate-700 transition flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>+ Add State Step</span>
            </button>

            <button onclick="document.getElementById('addTransitionModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-indigo-600/30 transition flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <span>+ Add Transition Rule</span>
            </button>

            <form action="{{ route('workflows.destroy', $workflow->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete workflow \'{{ $workflow->name }}\'?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-xl text-xs transition" title="Delete Workflow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Visual Interactive State Flow Canvas -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Workflow State Machine Layout</h3>
            <span class="text-[10px] text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20 flex items-center gap-1.5">
                <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
                Drag cards to reorder state positions
            </span>
        </div>
        
        <div id="statesContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($workflow->states as $state)
                @php
                    $colorClasses = [
                        'slate' => 'bg-slate-900 border-slate-700 text-slate-300',
                        'emerald' => 'bg-emerald-950/40 border-emerald-500/40 text-emerald-300',
                        'amber' => 'bg-amber-950/40 border-amber-500/40 text-amber-300',
                        'rose' => 'bg-rose-950/40 border-rose-500/40 text-rose-300',
                        'indigo' => 'bg-indigo-950/40 border-indigo-500/40 text-indigo-300',
                        'purple' => 'bg-purple-950/40 border-purple-500/40 text-purple-300',
                        'blue' => 'bg-blue-950/40 border-blue-500/40 text-blue-300',
                    ][$state->color] ?? 'bg-slate-900 border-slate-700 text-slate-300';
                @endphp

                <div data-state-id="{{ $state->id }}" class="border rounded-3xl p-5 shadow-2xl relative flex flex-col justify-between space-y-4 backdrop-blur-md transition-all duration-200 {{ $colorClasses }}">
                    <!-- State Header -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-white p-1 rounded hover:bg-white/10" title="Drag to reorder position">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                    </svg>
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-current"></span>
                                <h4 class="font-bold text-white text-base">{{ $state->name }}</h4>
                            </div>

                            <div class="flex items-center space-x-1">
                                @if($state->is_initial)
                                    <span class="px-2 py-0.5 rounded-full text-[9px] bg-amber-500/20 text-amber-300 font-extrabold uppercase border border-amber-500/30">Initial</span>
                                @endif
                                @if($state->is_final)
                                    <span class="px-2 py-0.5 rounded-full text-[9px] bg-emerald-500/20 text-emerald-300 font-extrabold uppercase border border-emerald-500/30">Terminal</span>
                                @endif

                                <!-- Edit State Button -->
                                <button type="button" onclick='openEditStateModal({{ json_encode($state) }})' class="text-slate-400 hover:text-indigo-300 p-1 transition" title="Edit State Step">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>

                                <!-- Delete State Button -->
                                <form action="{{ route('workflows.states.delete', $state->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete state \'{{ $state->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-400 p-1 transition" title="Delete State Step">&times;</button>
                                </form>
                            </div>
                        </div>

                        <div class="font-mono text-[10px] opacity-75">code: <span class="bg-black/30 px-1.5 py-0.5 rounded">{{ $state->code }}</span></div>
                    </div>

                    <!-- Outgoing Transitions Section -->
                    <div class="pt-3 border-t border-white/10 space-y-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider opacity-70 block">Outgoing Action Transitions ({{ $state->outgoingTransitions->count() }})</span>

                        <div class="space-y-2">
                            @forelse($state->outgoingTransitions as $t)
                                <div class="bg-slate-950/70 border border-white/10 rounded-2xl p-2.5 text-xs text-white space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-indigo-300 flex items-center space-x-1">
                                            <span>{{ $t->action_name }}</span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            <span class="text-emerald-300 font-semibold">{{ $t->toState->name }}</span>
                                        </span>

                                        <form action="{{ route('workflows.transitions.delete', $t->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-500 hover:text-rose-400 text-xs px-1">&times;</button>
                                        </form>
                                    </div>

                                    <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-white/5">
                                        <span>Roles: 
                                            @if(empty($t->allowed_roles))
                                                <strong class="text-slate-300">All Org Roles</strong>
                                            @else
                                                <strong class="text-amber-300">{{ implode(', ', $t->allowed_roles) }}</strong>
                                            @endif
                                        </span>
                                        @if($t->requires_note)
                                            <span class="text-amber-400 font-semibold">• Requires Note</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-[10px] text-slate-400 italic p-2 rounded bg-black/20 text-center">
                                    No outgoing transition rules defined from this state.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-900/40 border border-slate-800 rounded-3xl p-12 text-center text-slate-500">
                    No states added. Click "+ Add State Step" to configure workflow stages.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal: Add Workflow State -->
<div id="addStateModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add Workflow State Step</h3>
            <button onclick="document.getElementById('addStateModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('workflows.states.store', $workflow->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">State Step Name *</label>
                <input type="text" name="name" required placeholder="e.g. Awaiting Quality Inspection" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Badge Color Theme *</label>
                <select name="color" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="slate">Slate Gray</option>
                    <option value="amber">Amber Yellow (Pending)</option>
                    <option value="indigo">Indigo Blue (Processing)</option>
                    <option value="purple">Purple (Inspection)</option>
                    <option value="emerald">Emerald Green (Approved)</option>
                    <option value="rose">Rose Red (Rejected)</option>
                </select>
            </div>

            <div class="space-y-2 pt-1">
                <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                    <input type="checkbox" name="is_initial" value="1" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                    <span>Initial State (Entry point for new requisitions)</span>
                </label>

                <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                    <input type="checkbox" name="is_final" value="1" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                    <span>Terminal State (Triggers final stock issue / addition)</span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addStateModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save State Step</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Workflow State -->
<div id="editStateModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Edit Workflow State Step</h3>
            <button onclick="document.getElementById('editStateModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="editStateForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">State Step Name *</label>
                <input type="text" name="name" id="edit_state_name" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Badge Color Theme *</label>
                <select name="color" id="edit_state_color" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="slate">Slate Gray</option>
                    <option value="amber">Amber Yellow (Pending)</option>
                    <option value="indigo">Indigo Blue (Processing)</option>
                    <option value="purple">Purple (Inspection)</option>
                    <option value="emerald">Emerald Green (Approved)</option>
                    <option value="rose">Rose Red (Rejected)</option>
                </select>
            </div>

            <div class="space-y-2 pt-1">
                <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                    <input type="checkbox" name="is_initial" id="edit_state_is_initial" value="1" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                    <span>Initial State (Entry point for new requisitions)</span>
                </label>

                <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                    <input type="checkbox" name="is_final" id="edit_state_is_final" value="1" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                    <span>Terminal State (Triggers final stock issue / addition)</span>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('editStateModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Update State Step</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Transition Rule -->
<div id="addTransitionModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="font-bold text-white text-base">Add Transition Rule</h3>
            <button onclick="document.getElementById('addTransitionModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('workflows.transitions.store', $workflow->id) }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">From State *</label>
                    <select name="from_state_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        @foreach($workflow->states as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">To Target State *</label>
                    <select name="to_state_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        @foreach($workflow->states as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Action Button Label *</label>
                <input type="text" name="action_name" required placeholder="e.g. Approve & Dispatch Stock" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Allowed Organization Roles (select allowed roles)</label>
                <div class="space-y-2 max-h-32 overflow-y-auto p-2 bg-slate-950 border border-slate-800 rounded-xl">
                    @forelse($roles as $role)
                        <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer hover:bg-slate-900 p-1 rounded">
                            <input type="checkbox" name="allowed_roles[]" value="{{ $role->slug }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-white">{{ $role->name }}</span>
                            <span class="text-[10px] text-slate-500">({{ $role->slug }})</span>
                        </label>
                    @empty
                        <p class="text-[11px] text-slate-500">No custom roles defined. All organization users will be permitted.</p>
                    @endempty
                </div>
            </div>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="requires_note" id="requires_note_check" value="1" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                <label for="requires_note_check" class="text-xs text-slate-300">Require User to enter a Note / Reason when executing</label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('addTransitionModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30">Save Transition Rule</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditStateModal(state) {
        document.getElementById('editStateForm').action = "{{ url('/workflows/states') }}/" + state.id;
        document.getElementById('edit_state_name').value = state.name;
        document.getElementById('edit_state_color').value = state.color;
        document.getElementById('edit_state_is_initial').checked = state.is_initial ? true : false;
        document.getElementById('edit_state_is_final').checked = state.is_final ? true : false;
        document.getElementById('editStateModal').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('statesContainer');
        if (container && typeof Sortable !== 'undefined') {
            Sortable.create(container, {
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'opacity-40',
                onEnd: function () {
                    const stateIds = Array.from(container.children)
                        .map(card => card.dataset.stateId)
                        .filter(Boolean);

                    if (stateIds.length === 0) return;

                    fetch('{{ route("workflows.states.reorder", $workflow->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ state_ids: stateIds })
                    });
                }
            });
        }
    });
</script>
@endsection
