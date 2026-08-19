<!DOCTYPE html>
<html lang="en" class="h-full light" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Inventory Management System') }} - Authentication</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; transition: background 0.2s ease, color 0.2s ease; }

        html.light body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 50%, #f1f5f9 100%) !important;
            color: #0f172a !important;
        }

        html.light .bg-slate-900\/80,
        html.light .bg-slate-900 {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.08), 0 8px 10px -6px rgb(0 0 0 / 0.08) !important;
        }

        html.light input {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        html.light .text-white { color: #0f172a !important; }
        html.light .text-slate-300 { color: #475569 !important; }
        html.light .text-slate-400 { color: #64748b !important; }
        html.light .border-slate-800 { border-color: #e2e8f0 !important; }
    </style>
</head>
<body class="h-full flex items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-slate-100 p-4 relative">
    <!-- Top-right theme toggle on Auth page -->
    <div class="absolute top-6 right-6">
        <button onclick="toggleAuthTheme()" type="button" title="Toggle Light / Dark Mode" class="p-2.5 text-slate-600 hover:text-slate-900 bg-white/80 border border-slate-200 rounded-2xl transition flex items-center justify-center cursor-pointer shadow-lg backdrop-blur">
            <svg class="w-5 h-5 text-amber-500 theme-icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="w-5 h-5 text-indigo-400 theme-icon-moon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
    </div>

    <div class="w-full max-w-md">
        @yield('content')
    </div>

    <script>
        function toggleAuthTheme() {
            const isLight = document.documentElement.classList.contains('light');
            if (isLight) {
                document.documentElement.classList.remove('light');
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                syncAuthThemeUI('dark');
            } else {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                syncAuthThemeUI('light');
            }
        }

        function syncAuthThemeUI(currentTheme) {
            const sunIcon = document.querySelector('.theme-icon-sun');
            const moonIcon = document.querySelector('.theme-icon-moon');
            if (sunIcon && moonIcon) {
                if (currentTheme === 'light') {
                    sunIcon.classList.remove('hidden');
                    moonIcon.classList.add('hidden');
                } else {
                    sunIcon.classList.add('hidden');
                    moonIcon.classList.remove('hidden');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            syncAuthThemeUI(currentTheme);
        });
    </script>
</body>
</html>
