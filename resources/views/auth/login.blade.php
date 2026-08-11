@extends('layouts.auth')

@section('content')
<div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-indigo-500/10">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 mb-4 shadow-inner">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Inventory Nexus ERP</h1>
        <p class="text-xs text-slate-400 mt-1">Multi-Tenant Inventory & Dynamic Workflow Engine</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-rose-500/10 border border-rose-500/30 text-rose-300 px-4 py-3 rounded-2xl text-xs space-y-1">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Email Address</label>
            <input type="email" name="email" id="login_email" value="{{ old('email') }}" required autofocus
                   class="w-full bg-slate-950/70 border border-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 outline-none transition">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Password</label>
            <input type="password" name="password" id="login_password" required
                   class="w-full bg-slate-950/70 border border-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 outline-none transition">
        </div>

        <div class="flex items-center justify-between text-xs">
            <label class="flex items-center text-slate-400 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                <span class="ml-2">Remember me</span>
            </label>
        </div>

        <button type="submit" class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-sm shadow-lg shadow-indigo-600/30 transition duration-200">
            Sign In to Workspace
        </button>
    </form>

    <!-- Quick Demo Accounts Switcher -->
    <div class="mt-8 pt-6 border-t border-slate-800/80">
        <p class="text-xs font-semibold text-slate-400 text-center mb-3">Quick Demo Login Presets:</p>
        <div class="grid grid-cols-1 gap-2">
            <button onclick="fillAuth('superadmin@system.com', 'password')" class="text-left px-3 py-2 rounded-xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/50 text-xs transition flex items-center justify-between">
                <div>
                    <span class="font-bold text-amber-400 block">Super Admin</span>
                    <span class="text-slate-400 text-[10px]">Global Tenant Control</span>
                </div>
                <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full border border-amber-500/30">System</span>
            </button>

            <button onclick="fillAuth('admin@apexlogistics.com', 'password')" class="text-left px-3 py-2 rounded-xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/50 text-xs transition flex items-center justify-between">
                <div>
                    <span class="font-bold text-indigo-400 block">Org Admin: Apex Logistics</span>
                    <span class="text-slate-400 text-[10px]">Org Admin & Workflow Builder</span>
                </div>
                <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full border border-indigo-500/30">Tenant A</span>
            </button>

            <button onclick="fillAuth('manager@apexlogistics.com', 'password')" class="text-left px-3 py-2 rounded-xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/50 text-xs transition flex items-center justify-between">
                <div>
                    <span class="font-bold text-emerald-400 block">Org User: Inventory Manager</span>
                    <span class="text-slate-400 text-[10px]">Custom Role & Permissions</span>
                </div>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-500/30">Tenant A</span>
            </button>
        </div>
    </div>
</div>

<script>
    function fillAuth(email, password) {
        document.getElementById('login_email').value = email;
        document.getElementById('login_password').value = password;
    }
</script>
@endsection
