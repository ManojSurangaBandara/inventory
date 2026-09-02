<!DOCTYPE html>
<html lang="en" class="h-full light" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Inventory Management System') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        // Immediate Theme initialization: Default to Light Mode everywhere unless user explicitly chose 'dark'
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; transition: background-color 0.2s ease, color 0.2s ease; }

        /* ==========================================================================
           COMPREHENSIVE LIGHT MODE THEME ENGINE
           ========================================================================== */
        html.light, 
        html.light body {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        /* Sidebar Navigation in Light Mode */
        html.light aside {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        }

        html.light aside .border-b,
        html.light aside .border-t {
            border-color: #f1f5f9 !important;
        }

        html.light aside nav a {
            color: #475569 !important;
        }

        html.light aside nav a svg {
            color: #64748b !important;
            stroke: currentColor !important;
        }

        html.light aside nav a:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        html.light aside nav a:hover span {
            color: #0f172a !important;
        }

        html.light aside nav a:hover svg {
            color: #0f172a !important;
            stroke: #0f172a !important;
        }

        html.light aside nav a.bg-indigo-600\/20,
        html.light aside nav a.bg-indigo-600\/20:hover {
            background-color: #eef2ff !important;
            color: #4338ca !important;
            border-color: #c7d2fe !important;
        }

        html.light aside nav a.bg-indigo-600\/20 span,
        html.light aside nav a.bg-indigo-600\/20 svg,
        html.light aside nav a.bg-indigo-600\/20:hover span,
        html.light aside nav a.bg-indigo-600\/20:hover svg {
            color: #4338ca !important;
            stroke: #4338ca !important;
        }

        html.light aside .grid a {
            background-color: #ffffff !important;
            color: #475569 !important;
            border-color: #e2e8f0 !important;
        }

        html.light aside .grid a:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        /* Top Header in Light Mode */
        html.light header {
            background-color: rgba(255, 255, 255, 0.9) !important;
            border-color: #e2e8f0 !important;
            backdrop-filter: blur(12px);
        }

        html.light header h1 {
            color: #0f172a !important;
        }

        html.light main {
            background-color: #f8fafc !important;
        }

        /* Cards, Panels, and Containers */
        html.light .bg-slate-950,
        html.light .bg-slate-900,
        html.light .bg-slate-900\/80,
        html.light .bg-slate-900\/50,
        html.light .bg-slate-900\/40 {
            background-color: #ffffff !important;
        }

        /* Inner blocks, Table headers & nested panels */
        html.light .bg-slate-950\/80,
        html.light .bg-slate-950\/60,
        html.light .bg-slate-950\/40 {
            background-color: #f8fafc !important;
        }

        html.light .bg-slate-800,
        html.light .bg-slate-800\/80,
        html.light .bg-slate-800\/60,
        html.light .bg-slate-800\/30 {
            background-color: #f1f5f9 !important;
        }

        /* All Modal Windows - Universal Selector across the entire application */
        html.light [id*="Modal"] > div,
        html.light [id*="modal"] > div,
        html.light .fixed.inset-0 > div {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.15), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
            color: #0f172a !important;
        }

        /* Modal backdrop overlay */
        html.light .fixed.inset-0[class*="bg-slate-950"],
        html.light .fixed.inset-0.bg-slate-950\/80,
        html.light .fixed.inset-0.bg-slate-950\/85 {
            background-color: rgba(15, 23, 42, 0.65) !important;
        }

        /* Inner blocks & sub-panels inside Modals in Light Mode */
        html.light .fixed.inset-0 [class*="bg-slate-950"],
        html.light .fixed.inset-0 [class*="bg-slate-900"] {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }
        
        html.light .fixed.inset-0 label:hover {
            background-color: #f1f5f9 !important;
        }

        /* Form Inputs, Selects, and Textareas */
        html.light input:not([type="checkbox"]):not([type="radio"]),
        html.light select,
        html.light textarea {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        html.light input::placeholder,
        html.light textarea::placeholder {
            color: #94a3b8 !important;
        }

        html.light label {
            color: #334155 !important;
        }

        /* Borders & Dividers */
        html.light .border-slate-800,
        html.light .border-slate-800\/80,
        html.light .border-slate-800\/60,
        html.light .border-slate-700,
        html.light .border-slate-700\/60,
        html.light .divide-slate-800,
        html.light .divide-slate-800\/60,
        /* Typography */
        html.light h1,
        html.light h2,
        html.light h3,
        html.light h4,
        html.light h5,
        html.light h6 {
            color: #0f172a !important;
        }

        html.light th {
            color: #475569 !important;
        }

        html.light td.text-white,
        html.light p.text-white,
        html.light div.text-white:not([class*="bg-indigo"]):not([class*="bg-emerald"]):not([class*="bg-rose"]):not([class*="bg-amber"]):not([class*="bg-purple"]):not([class*="bg-blue"]):not(button):not(button *):not(a[class*="bg-"]):not(a[class*="bg-"] *),
        html.light span.text-white:not([class*="bg-indigo"]):not([class*="bg-emerald"]):not([class*="bg-rose"]):not([class*="bg-amber"]):not([class*="bg-purple"]):not([class*="bg-blue"]):not(button *):not(a[class*="bg-"] *):not(.tab-btn *) {
            color: #0f172a !important;
        }

        html.light .text-slate-100 {
            color: #1e293b !important;
        }
        html.light .text-slate-200 {
            color: #334155 !important;
        }
        html.light .text-slate-300 {
            color: #475569 !important;
        }
        html.light .text-slate-400 {
            color: #64748b !important;
        }
        html.light .text-slate-500 {
            color: #64748b !important;
        }

        /* High-contrast status accents in Light Mode */
        html.light .text-indigo-400 { color: #4f46e5 !important; }
        html.light .text-indigo-300 { color: #4338ca !important; }
        html.light .text-emerald-400 { color: #059669 !important; }
        html.light .text-emerald-300 { color: #047857 !important; }
        html.light .text-amber-400 { color: #d97706 !important; }
        html.light .text-amber-300 { color: #b45309 !important; }
        html.light .text-rose-400 { color: #e11d48 !important; }
        html.light .text-rose-300 { color: #be123c !important; }
        html.light .text-purple-400 { color: #7e22ce !important; }
        html.light .text-purple-300 { color: #6b21a8 !important; }

        /* Badges & Alert boxes in Light Mode */
        html.light .bg-indigo-950\/30,
        html.light .bg-indigo-950\/40,
        html.light .bg-indigo-500\/10,
        html.light .bg-indigo-500\/20 {
            background-color: #eef2ff !important;
            border-color: #c7d2fe !important;
            color: #4338ca !important;
        }

        html.light .bg-emerald-950\/30,
        html.light .bg-emerald-500\/10,
        html.light .bg-emerald-500\/20 {
            background-color: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            color: #065f46 !important;
        }

        html.light .bg-rose-950\/40,
        html.light .bg-rose-500\/10,
        html.light .bg-rose-500\/20 {
            background-color: #fff1f2 !important;
            border-color: #fecdd3 !important;
            color: #9f1239 !important;
        }

        html.light .bg-amber-500\/10,
        html.light .bg-amber-500\/20 {
            background-color: #fffbeb !important;
            border-color: #fde68a !important;
            color: #92400e !important;
        }

        html.light .bg-purple-500\/10,
        html.light .bg-purple-500\/20 {
            background-color: #faf5ff !important;
            border-color: #e9d5ff !important;
            color: #6b21a8 !important;
        }

        /* Banner gradients in Light Mode */
        html.light .from-slate-900 {
            --tw-gradient-from: #f8fafc !important;
        }
        html.light .via-indigo-950 {
            --tw-gradient-stops: var(--tw-gradient-from), #e0e7ff 50%, var(--tw-gradient-to) !important;
        }
        html.light .to-slate-900 {
            --tw-gradient-to: #f8fafc !important;
        }

        /* Table header and rows */
        html.light thead {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border-color: #e2e8f0 !important;
        }

        html.light tr:hover {
            background-color: #f8fafc !important;
        }

        /* Workflow state cards in builder */
        html.light .workflow-state-card {
            background-color: #ffffff !important;
            border-width: 1px !important;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05), 0 4px 6px -4px rgb(0 0 0 / 0.02) !important;
        }

        html.light .workflow-state-card .state-title {
            color: #0f172a !important;
        }

        html.light .workflow-state-card.state-card-emerald {
            border-color: #a7f3d0 !important;
            border-top: 5px solid #10b981 !important;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 25%) !important;
        }
        html.light .workflow-state-card.state-card-amber {
            border-color: #fde68a !important;
            border-top: 5px solid #f59e0b !important;
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 25%) !important;
        }
        html.light .workflow-state-card.state-card-rose {
            border-color: #fecdd3 !important;
            border-top: 5px solid #f43f5e !important;
            background: linear-gradient(180deg, #fff1f2 0%, #ffffff 25%) !important;
        }
        html.light .workflow-state-card.state-card-indigo {
            border-color: #c7d2fe !important;
            border-top: 5px solid #6366f1 !important;
            background: linear-gradient(180deg, #eef2ff 0%, #ffffff 25%) !important;
        }
        html.light .workflow-state-card.state-card-purple {
            border-color: #e9d5ff !important;
            border-top: 5px solid #a855f7 !important;
            background: linear-gradient(180deg, #faf5ff 0%, #ffffff 25%) !important;
        }
        html.light .workflow-state-card.state-card-blue {
            border-color: #bae6fd !important;
            border-top: 5px solid #0ea5e9 !important;
            background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 25%) !important;
        }
        html.light .workflow-state-card.state-card-slate {
            border-color: #e2e8f0 !important;
            border-top: 5px solid #94a3b8 !important;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 25%) !important;
        }

        html.light .state-transition-card {
            background-color: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            color: #0f172a !important;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05) !important;
        }

        html.light .state-code-pill {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }

        html.light .state-empty-box {
            background-color: #f8fafc !important;
            border: 1px dashed #cbd5e1 !important;
            color: #64748b !important;
        }

        /* Tab navigation buttons in Light Mode */
        html.light .tab-btn.bg-slate-800\/80,
        html.light .tab-btn.bg-slate-800 {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
        }
        html.light .tab-btn.bg-slate-800\/80:hover,
        html.light .tab-btn.bg-slate-800:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }

        /* Timeline & Audit Trails in Light Mode */
        html.light .border-l-2.border-slate-800 {
            border-color: #e2e8f0 !important;
        }
        html.light .border-slate-900 {
            border-color: #ffffff !important;
        }

        /* Notifications & Alert cards in Light Mode */
        html.light .bg-slate-800\/40 {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }

        /* Code snippets & lot pills in Light Mode */
        html.light .bg-black\/30,
        html.light .bg-black\/20,
        html.light .bg-rose-900\/60 {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            border: 1px solid #e2e8f0 !important;
        }

        /* Role & Permission Cards in Light Mode */
        html.light .role-card {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            border-top: 4px solid #6366f1 !important;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.04), 0 4px 6px -4px rgb(0 0 0 / 0.02) !important;
        }

        html.light .role-card .role-name {
            color: #0f172a !important;
        }

        html.light .role-card .user-count-badge {
            background-color: #eef2ff !important;
            color: #4338ca !important;
            border-color: #c7d2fe !important;
        }

        html.light .role-perm-chip {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            border-color: #e2e8f0 !important;
        }

        html.light .role-perm-chip:hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }

        html.light .role-config-btn {
            background-color: #eef2ff !important;
            color: #4338ca !important;
            border: 1px solid #c7d2fe !important;
        }

        html.light .role-config-btn:hover {
            background-color: #e0e7ff !important;
            color: #3730a3 !important;
        }

        /* Modal Permission Module Cards in Light Mode */
        html.light .perm-module-card {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }

        html.light .perm-module-card h4 {
            color: #0f172a !important;
            border-color: #e2e8f0 !important;
        }

        html.light .perm-checkbox-label:hover {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
        }

        /* Primary Action Buttons (Enforce pure white text & icons in all modes) */
        html.light .bg-indigo-600,
        html.light .bg-indigo-500,
        html.light .bg-emerald-600,
        html.light .bg-emerald-500,
        html.light .bg-rose-600,
        html.light .bg-rose-500,
        html.light .bg-amber-600,
        html.light .bg-purple-600,
        html.light .bg-purple-500,
        html.light .bg-blue-600,
        html.light .bg-blue-500,
        html.light .bg-indigo-600 *,
        html.light .bg-indigo-500 *,
        html.light .bg-emerald-600 *,
        html.light .bg-emerald-500 *,
        html.light .bg-rose-600 *,
        html.light .bg-rose-500 *,
        html.light .bg-purple-600 *,
        html.light .bg-blue-600 *,
        html.light button.bg-indigo-600,
        html.light button.bg-indigo-600 *,
        html.light button.bg-indigo-500,
        html.light button.bg-indigo-500 *,
        html.light a.bg-indigo-600,
        html.light a.bg-indigo-600 *,
        html.light .tab-btn.bg-indigo-600,
        html.light .tab-btn.bg-indigo-600 * {
            color: #ffffff !important;
            fill: none;
        }

        html.light .bg-indigo-600 svg,
        html.light .bg-indigo-500 svg,
        html.light .bg-emerald-600 svg,
        html.light .bg-rose-600 svg,
        html.light .bg-purple-600 svg,
        html.light .bg-blue-600 svg {
            stroke: #ffffff !important;
            color: #ffffff !important;
        }

        /* High-Contrast Interactive Hover States in Main Content */
        html.light main .hover\:text-white:hover,
        html.light main .hover\:text-white:hover *,
        html.light main [class*="hover:bg-indigo-600"]:hover,
        html.light main [class*="hover:bg-indigo-600"]:hover *,
        html.light main [class*="hover:bg-indigo-500"]:hover,
        html.light main [class*="hover:bg-indigo-500"]:hover *,
        html.light main [class*="hover:bg-emerald-600"]:hover,
        html.light main [class*="hover:bg-emerald-600"]:hover *,
        html.light main [class*="hover:bg-rose-600"]:hover,
        html.light main [class*="hover:bg-rose-600"]:hover *,
        html.light main [class*="hover:bg-purple-600"]:hover,
        html.light main [class*="hover:bg-purple-600"]:hover *,
        html.light main [class*="hover:bg-blue-600"]:hover,
        html.light main [class*="hover:bg-blue-600"]:hover *,
        html.light main button:hover.bg-indigo-600,
        html.light main button:hover.bg-indigo-600 *,
        html.light main a:hover.bg-indigo-600,
        html.light main a:hover.bg-indigo-600 * {
            color: #ffffff !important;
            stroke: #ffffff !important;
        }

        /* Action Transition Pills in Tables (Normal & Hover) */
        html.light .bg-indigo-600\/20:not(:hover) {
            background-color: #eef2ff !important;
            color: #4338ca !important;
            border-color: #c7d2fe !important;
        }

        html.light .bg-indigo-600\/20:hover {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            border-color: #4f46e5 !important;
        }

        html.light .bg-indigo-600\/20:hover * {
            color: #ffffff !important;
            stroke: #ffffff !important;
        }

        /* Secondary and Outline buttons in light mode */
        html.light button.border-slate-700,
        html.light button.border-slate-800,
        html.light a.border-slate-700,
        html.light a.border-slate-800 {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
        }

        html.light button.border-slate-700:hover,
        html.light button.border-slate-800:hover,
        html.light a.border-slate-700:hover,
        html.light a.border-slate-800:hover,
        html.light button[class*="hover:bg-slate"]:hover,
        html.light a[class*="hover:bg-slate"]:hover,
        html.light .bg-slate-800.text-slate-300:hover,
        html.light .bg-slate-800.text-white:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
        }

        html.light button.border-slate-700:hover *,
        html.light button.border-slate-800:hover *,
        html.light a.border-slate-700:hover *,
        html.light a.border-slate-800:hover * {
            color: #0f172a !important;
            stroke: #0f172a !important;
        }

        html.light .bg-slate-800.text-slate-300,
        html.light .bg-slate-800.text-white {
            background-color: #f1f5f9 !important;
            color: #334155 !important;
            border-color: #cbd5e1 !important;
        }
    </style>
</head>
<body class="h-full bg-slate-950 flex flex-col md:flex-row antialiased font-sans">

    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 bg-slate-900 border-r border-slate-800/80 flex flex-col shrink-0">
        <!-- Logo & Organization Header -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 font-extrabold text-xs">
                    IMS
                </div>
                <div>
                    <h2 class="font-bold text-white text-sm leading-snug">Inventory Management System</h2>
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
                    <span>Dashboard</span>
                </a>

                @if(Auth::user()->is_org_admin || Auth::user()->is_super_admin)
                    <div class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Master Data</div>
                    <a href="{{ route('inventory.items') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('inventory.items*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <span>Items</span>
                    </a>

                    <div class="grid grid-cols-3 gap-1 px-1 pt-1">
                        <a href="{{ route('inventory.categories') }}" class="text-center py-1.5 rounded-lg text-[10px] font-medium text-slate-400 hover:bg-slate-800 hover:text-white border border-slate-800 {{ request()->routeIs('inventory.categories*') ? 'border-indigo-500/50 text-indigo-300' : '' }}">Categories</a>
                        <a href="{{ route('inventory.suppliers') }}" class="text-center py-1.5 rounded-lg text-[10px] font-medium text-slate-400 hover:bg-slate-800 hover:text-white border border-slate-800 {{ request()->routeIs('inventory.suppliers*') ? 'border-indigo-500/50 text-indigo-300' : '' }}">Suppliers</a>
                        <a href="{{ route('inventory.warehouses') }}" class="text-center py-1.5 rounded-lg text-[10px] font-medium text-slate-400 hover:bg-slate-800 hover:text-white border border-slate-800 {{ request()->routeIs('inventory.warehouses*') ? 'border-indigo-500/50 text-indigo-300' : '' }}">Warehouses</a>
                    </div>
                @endif

                <div class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Operations & Pipelines</div>
                <a href="{{ route('stock.balance') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stock.balance*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Stock Balance</span>
                </a>
                <a href="{{ route('stock.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stock.index*') || request()->routeIs('stock.show') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span>Stock Requests</span>
                </a>

                {{-- Temporarily hidden: Purchase Orders
                <a href="{{ route('orders.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orders.*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Purchase Orders</span>
                </a>
                --}}

                @if(Auth::user()->is_org_admin || Auth::user()->is_super_admin)
                    <div class="px-3 pt-4 pb-1 text-[10px] uppercase font-bold text-slate-500 tracking-wider">Org Administration</div>
                    <a href="{{ route('workflows.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('workflows.*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Workflow Builder</span>
                    </a>
                    {{-- Temporarily hidden: API Integration Keys
                    <a href="{{ route('orgadmin.tokens') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orgadmin.tokens*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 0121 9z"/></svg>
                        <span>API Integration Keys</span>
                    </a>
                    --}}
                    <a href="{{ route('orgadmin.users') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orgadmin.users*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Users</span>
                    </a>
                    <a href="{{ route('orgadmin.roles') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('orgadmin.roles*') ? 'bg-indigo-600/20 text-indigo-400 font-semibold border border-indigo-500/30' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Roles & Permissions</span>
                    </a>
                @endif
            @endif

        </nav>

        <!-- User Profile & Footer Controls -->
        <div class="p-4 border-t border-slate-800 bg-slate-900/50 space-y-3">
            <div class="flex items-center justify-between">
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
                <!-- Theme Toggle Button (Light / Dark Mode) -->
                <button onclick="toggleTheme()" type="button" id="themeToggleButton" title="Toggle Light / Dark Mode" class="p-2 text-slate-400 hover:text-white bg-slate-800/60 border border-slate-700/60 rounded-xl transition flex items-center justify-center space-x-1.5 cursor-pointer shadow-sm">
                    <!-- Sun Icon (Active when in Light Mode) -->
                    <svg class="w-4 h-4 text-amber-500 theme-icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon Icon (Active when in Dark Mode) -->
                    <svg class="w-4 h-4 text-indigo-400 theme-icon-moon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <span class="text-[11px] font-semibold text-slate-600 theme-toggle-label hidden sm:inline">Light</span>
                </button>

                {{-- Temporarily hidden: Notifications Bell Icon
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[9px] font-extrabold rounded-full flex items-center justify-center border-2 border-slate-950">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>
                @endif
                --}}

                @if(Auth::user()->is_super_admin)
                    <span class="px-2.5 py-1 text-xs font-medium bg-amber-500/10 text-amber-600 border border-amber-500/30 rounded-lg flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Super Admin Mode</span>
                    </span>
                @else
                    <span class="px-2.5 py-1 text-xs font-medium bg-indigo-500/10 text-indigo-600 border border-indigo-500/30 rounded-lg flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                        <span>Org: {{ Auth::user()->organization->name ?? 'Default' }}</span>
                    </span>
                @endif
            </div>
        </header>

        <!-- Notification Flash Messages -->
        <div class="p-6 pb-0 space-y-3">
            @if(session('success'))
                <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 text-xs px-4 py-3 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-rose-500/10 border border-rose-500/30 text-rose-600 text-xs px-4 py-3 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="p-6 flex-1">
            @yield('content')
        </div>
    </main>

    <script>
        function toggleTheme() {
            const isLight = document.documentElement.classList.contains('light');
            if (isLight) {
                document.documentElement.classList.remove('light');
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                syncThemeUI('dark');
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: 'dark' } }));
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                syncThemeUI('light');
                window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: 'light' } }));
            }
        }

        function syncThemeUI(currentTheme) {
            const sunIcons = document.querySelectorAll('.theme-icon-sun');
            const moonIcons = document.querySelectorAll('.theme-icon-moon');
            const themeLabels = document.querySelectorAll('.theme-toggle-label');

            if (currentTheme === 'light') {
                sunIcons.forEach(el => el.classList.remove('hidden'));
                moonIcons.forEach(el => el.classList.add('hidden'));
                themeLabels.forEach(el => el.textContent = 'Light');
            } else {
                sunIcons.forEach(el => el.classList.add('hidden'));
                moonIcons.forEach(el => el.classList.remove('hidden'));
                themeLabels.forEach(el => el.textContent = 'Dark');
            }
        }

        // Synchronize icon states on DOM content loaded
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            syncThemeUI(currentTheme);
        });
    </script>
</body>
</html>
