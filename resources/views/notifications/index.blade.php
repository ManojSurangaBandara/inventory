@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-3">
                <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Notifications & Alerts
            </h1>
            <p class="text-slate-400 text-sm mt-1">Track request status updates, approval tasks, and rejection notices.</p>
        </div>
        <div>
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark All as Read
                </button>
            </form>
        </div>
    </div>

    <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-6 shadow-xl">
        @if($notifications->isEmpty())
            <div class="text-center py-12 text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-lg font-medium text-slate-300">No notifications found</p>
                <p class="text-sm text-slate-500 mt-1">You are all caught up!</p>
            </div>
        @else
            <div class="divide-y divide-slate-700/50">
                @foreach($notifications as $n)
                    <div class="py-4 flex items-start justify-between gap-4 {{ $n->is_read ? 'opacity-70' : 'bg-slate-800/40' }} p-4 rounded-xl transition">
                        <div class="flex items-start gap-4">
                            <div class="mt-1">
                                @if($n->type === 'rejected')
                                    <span class="p-2.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl inline-block">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                @elseif($n->type === 'approval_needed')
                                    <span class="p-2.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-xl inline-block">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                @elseif($n->type === 'completed')
                                    <span class="p-2.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl inline-block">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                @else
                                    <span class="p-2.5 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-xl inline-block">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                @endif
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-100 text-base flex items-center gap-2">
                                    {{ $n->title }}
                                    @if(!$n->is_read)
                                        <span class="px-2 py-0.5 text-xs font-semibold bg-indigo-500 text-white rounded-full">New</span>
                                    @endif
                                </h4>
                                <p class="text-sm text-slate-300 mt-1 leading-relaxed">{{ $n->message }}</p>
                                <span class="text-xs text-slate-500 mt-2 block">{{ $n->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($n->link_url)
                                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition">
                                        View Details
                                    </button>
                                </form>
                            @elseif(!$n->is_read)
                                <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-medium rounded-lg transition">
                                        Dismiss
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
