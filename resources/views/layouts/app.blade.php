<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Inventory ERP') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-950 flex flex-col md:flex-row antialiased font-sans">

    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 bg-slate-900 border-r border-slate-800/80 flex flex-col shrink-0">
        <!-- Logo & Organization Header -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 font-extrabold text-lg">
                    IN
                </div>
                <div>
                    <h2 class="font-bold text-white text-sm leading-snug">Nexus ERP</h2>
                    @if (Auth::user()->is_super_admin)
                        <span class="text-[10px] bg-amber-500/20 text-amber-300 font-semibold px-2 py-0.5 rounded-full border border-amber-500/30">Super Admin Portal</span>
                    @elseif(Auth::user()->organization)
                        <span class="text-[10px] bg-indigo-500/20 text-indigo-300 font-semibold px-2 py-0.5 rounded-full border border-indigo-500/30">{{ Auth::user()->organization->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1 text-xs font-medium flex-1">

            @if(Auth::user()->is_super_admin)
                <div class="px-3 pt-2 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Super Admin</div>
                <a href="{{ route('superadmin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('superadmin.dashboard') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Platform Overview</span>
                </a>
                <a href="{{ route('superadmin.organizations') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('superadmin.organizations*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Organizations</span>
                </a>
            @else
                <div class="px-3 pt-2 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Main</div>
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Tenant Dashboard</span>
                </a>

                @if(Auth::user()->is_org_admin || Auth::user()->is_super_admin)
                    <div class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Master Data</div>
                    <a href="{{ route('inventory.items') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('inventory.items*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Item Master Catalog</span>
                    </a>

                    <div class="grid grid-cols-3 gap-1 px-1 pt-1">
                        <a href="{{ route('inventory.categories') }}" class="text-center py-1.5 rounded-lg text-[10px] font-medium text-slate-400 hover:bg-slate-800 hover:text-white border border-slate-800 {{ request()->routeIs('inventory.categories*') ? 'border-indigo-500/50 text-indigo-300' : '' }}">Categories</a>
                        <a href="{{ route('inventory.suppliers') }}" class="text-center py-1.5 rounded-lg text-[10px] font-medium text-slate-400 hover:bg-slate-800 hover:text-white border border-slate-800 {{ request()->routeIs('inventory.suppliers*') ? 'border-indigo-500/50 text-indigo-300' : '' }}">Suppliers</a>
                        <a href="{{ route('inventory.warehouses') }}" class="text-center py-1.5 rounded-lg text-[10px] font-medium text-slate-400 hover:bg-slate-800 hover:text-white border border-slate-800 {{ request()->routeIs('inventory.warehouses*') ? 'border-indigo-500/50 text-indigo-300' : '' }}">Warehouses</a>
                    </div>
                @endif

                <div class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Operations & Pipelines</div>
                <a href="{{ route('stock.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stock.*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span>Stock Requests & Issues</span>
                </a>

                <a href="{{ route('orders.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orders.*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Purchase Orders</span>
                </a>

                @if(Auth::user()->is_org_admin || Auth::user()->is_super_admin)
                    <div class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Org Administration</div>
                    <a href="{{ route('workflows.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('workflows.*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>UI Workflow Builder</span>
                    </a>
                    <a href="{{ route('orgadmin.tokens') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orgadmin.tokens*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 0121 9z"/></svg>
                        <span>API Integration Keys</span>
                    </a>
                    <a href="{{ route('orgadmin.users') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orgadmin.users*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Organization Users</span>
                    </a>
                    <a href="{{ route('orgadmin.roles') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orgadmin.roles*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Roles & Permissions</span>
                    </a>
                @endif
            @endif

        </nav>

        <!-- User Profile Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-900/50 flex items-center justify-between">
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="truncate">
                    <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" title="Logout" class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 bg-slate-950 overflow-y-auto">
        <!-- Top App Bar -->
        <header class="h-16 border-b border-slate-800/80 bg-slate-900/40 backdrop-blur border-b px-6 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center space-x-4">
                <h1 class="text-base font-bold text-white">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Notifications Bell Icon -->
                @if(Auth::user()->organization_id)
                    @php
                        $unreadCount = \App\Models\Notification::where('organization_id', Auth::user()->organization_id)
                            ->where(function($q) {
                                $q->where('user_id', Auth::id())->orWhereNull('user_id');
                            })
                            ->where('is_read', false)
                            ->count();
                    @endphp
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-slate-400 hover:text-white bg-slate-800/60 border border-slate-700/60 rounded-xl transition flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center border-2 border-slate-950">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>
                @endif

                @if(Auth::user()->is_super_admin)
                    <span class="px-2.5 py-1 text-xs font-medium bg-amber-500/10 text-amber-300 border border-amber-500/30 rounded-lg flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>Super Admin Mode</span>
                    </span>
                @else
                    <span class="px-2.5 py-1 text-xs font-medium bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 rounded-lg flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        <span>Org: {{ Auth::user()->organization->name ?? 'Default' }}</span>
                    </span>
                @endif
            </div>
        </header>

        <!-- Notification Flash Messages -->
        <div class="p-6 pb-0 space-y-3">
            @if(session('success'))
                <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs px-4 py-3 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs px-4 py-3 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="p-6 flex-1">
            @yield('content')
        </div>
    </main>

</body>
</html>
