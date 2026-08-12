@extends('layouts.app')

@section('title', 'API Integration Tokens')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-3">
                <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 0121 9z" />
                </svg>
                External API Tokens (Workshop Management System)
            </h1>
            <p class="text-slate-400 text-sm mt-1">Generate API keys to allow external systems to submit automated item requests.</p>
        </div>
        <button onclick="document.getElementById('createTokenModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition flex items-center gap-2 shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Generate New API Token
        </button>
    </div>

    <!-- API Info Card -->
    <div class="bg-indigo-950/40 border border-indigo-500/30 rounded-2xl p-5 text-indigo-200">
        <h3 class="font-semibold text-indigo-100 mb-2 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Workshop Management System API Endpoint
        </h3>
        <p class="text-xs text-indigo-300 mb-3">External systems can submit requests using the following endpoint and header:</p>
        <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-700 font-mono text-xs text-emerald-400 space-y-1">
            <div>POST <span class="text-slate-200">{{ url('/api/v1/item-requests') }}</span></div>
            <div class="text-slate-400">Header: <span class="text-amber-300">X-API-Key: &lt;YOUR_API_TOKEN&gt;</span></div>
            <div class="text-slate-400">Body: <span class="text-indigo-300">{"sku": "LAP-XPS15", "quantity": 5, "lot_number": "LOT-WMS-999", "notes": "Engine Workshop Request"}</span></div>
        </div>
    </div>

    <!-- Tokens Table -->
    <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-900/60 text-slate-400 uppercase text-xs border-b border-slate-700/60">
                <tr>
                    <th class="px-6 py-4">Application / Token Name</th>
                    <th class="px-6 py-4">Token Key</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Last Used</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @forelse($tokens as $t)
                    <tr class="hover:bg-slate-700/30 transition">
                        <td class="px-6 py-4 font-semibold text-slate-100">
                            {{ $t->name }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-indigo-300">
                            {{ $t->token }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                {{ ucfirst($t->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-400 text-xs">
                            {{ $t->last_used_at ? $t->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('orgadmin.tokens.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Revoke this API token?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 bg-rose-500/10 border border-rose-500/20 text-rose-400 hover:bg-rose-500/20 rounded-lg text-xs transition">
                                    Revoke Token
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">No API integration tokens generated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="createTokenModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <h3 class="text-xl font-bold text-slate-100">Generate External API Token</h3>
        <p class="text-slate-400 text-xs">Assign a recognizable name for the integration (e.g. "Workshop System Server").</p>
        <form action="{{ route('orgadmin.tokens.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-slate-300 text-sm font-medium mb-1">Integration Application Name</label>
                <input type="text" name="name" required placeholder="Workshop Management System" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-slate-100 focus:outline-none focus:border-indigo-500">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('createTokenModal').classList.add('hidden')" class="px-4 py-2 text-slate-400 hover:text-slate-200 text-sm font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-semibold transition">Generate Token</button>
            </div>
        </form>
    </div>
</div>
@endsection
