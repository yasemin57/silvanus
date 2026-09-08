<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SILVANUS – Orman Yangını Erken Tespit ve Müdahale Komuta Sistemi</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet Map CSS for high-performance interactive maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Tailwind CSS CDN for instant rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        silvanus: {
                            bg: '#080C14',
                            card: '#0F172A',
                            border: '#1E293B',
                            primary: '#EF4444',
                            amber: '#F59E0B',
                            emerald: '#10B981',
                            cyan: '#06B6D4',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background-color: #080C14;
            color: #F1F5F9;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-panel-glow {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.15);
        }

        /* Pulse Radar effect for wildfire marker */
        @keyframes fire-pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .fire-marker-pulse {
            animation: fire-pulse 2s infinite;
        }

        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #080C14; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        .leaflet-container {
            background: #0b1120 !important;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .leaflet-popup-content-wrapper {
            background: #0f172a !important;
            color: #f8fafc !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 12px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5) !important;
        }
        .leaflet-popup-tip { background: #0f172a !important; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-rose-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-50 glass-panel border-b border-slate-800/80 px-4 lg:px-8 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Brand & Region -->
            <div class="flex items-center space-x-4">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 via-orange-500 to-amber-400 flex items-center justify-center shadow-lg shadow-rose-500/20 group-hover:scale-105 transition duration-200">
                        <span class="text-xl">🔥</span>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">SILVANUS</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 border border-rose-500/30">AI Early Warning</span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium">Orman Yangını Erken Tespit, İzleme ve İtfaiye Haberleşme Sistemi</p>
                    </div>
                </a>
            </div>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center space-x-1 bg-slate-900/60 p-1.5 rounded-xl border border-slate-800">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    🗺️ Canlı Komuta Haritası
                </a>
                <a href="{{ route('incidents.index') }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('incidents.*') ? 'bg-rose-600 text-white shadow-lg shadow-rose-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    🚨 Yangın Olayları
                </a>
                <a href="{{ route('simulator') }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('simulator') ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    ⚡ AI & Edge Simülatörü
                </a>
            </nav>

            <!-- Status badges & Live Clock -->
            <div class="flex items-center space-x-3">
                <div class="hidden lg:flex items-center space-x-2 bg-slate-900/80 px-3 py-1.5 rounded-lg border border-slate-800 text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="text-slate-400">Yerel Sunucu:</span>
                    <span class="text-emerald-400 font-semibold">Çevrimiçi (42ms)</span>
                </div>

                <div id="liveClock" class="font-mono text-xs text-slate-400 bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800">
                    --:--:--
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 lg:p-8 space-y-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="glass-panel border-t border-slate-800/80 mt-auto py-4 px-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                © {{ date('Y') }} SILVANUS – Bütünleşik Orman Yangını Erken Tespit ve İtfaiye Koordinasyon Altyapısı.
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Edge Telemetri Aktif</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    <span>Google Maps Platform</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <span>FCM Push Notification</span>
                </span>
            </div>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('tr-TR', { hour12: false });
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    @stack('scripts')
</body>
</html>
