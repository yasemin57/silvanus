@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Fires -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-rose-500/40 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Aktif Orman Yangınları</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1 flex items-center space-x-2">
                        <span>{{ $health['incidents']['active_count'] }}</span>
                        @if($health['incidents']['active_count'] > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/40 animate-pulse">
                                KRİTİK ALARM
                            </span>
                        @endif
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-2xl">
                    🔥
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3 flex items-center space-x-1">
                <span class="text-rose-400 font-medium">{{ $health['incidents']['critical_count'] }} kritik</span>
                <span>olay AI ve sensörlerle izleniyor</span>
            </p>
        </div>

        <!-- Sensors Fleet -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-emerald-500/40 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Orman Sensörleri</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1">
                        {{ $health['sensors']['active'] }}<span class="text-base text-slate-500 font-normal">/{{ $health['sensors']['total'] }}</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-2xl">
                    📡
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3 flex items-center space-x-2">
                <span class="inline-flex items-center text-emerald-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span>
                    %{{ $health['sensors']['health_percentage'] }} Sağlık
                </span>
                @if($health['sensors']['offline'] > 0)
                    <span class="text-rose-400">({{ $health['sensors']['offline'] }} Çevrimdışı)</span>
                @endif
            </p>
        </div>

        <!-- Cameras Fleet -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-cyan-500/40 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">4K Termal Kameralar</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1">
                        {{ $health['cameras']['active'] }}<span class="text-base text-slate-500 font-normal">/{{ $health['cameras']['total'] }}</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-2xl">
                    📷
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3 flex items-center space-x-2">
                <span class="text-cyan-400 font-medium">Optik + Termal PTZ</span>
                <span>Canlı AI Gözetim</span>
            </p>
        </div>

        <!-- Fire Stations -->
        <div class="glass-panel p-5 rounded-2xl relative overflow-hidden group hover:border-amber-500/40 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Müdahale İstasyonları</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1">
                        {{ $stations->count() }}
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-2xl">
                    🏢
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3 flex items-center space-x-1">
                <span class="text-amber-400 font-medium">Haversine Sevk Motoru</span>
                <span>Aktif</span>
            </p>
        </div>
    </div>

    <!-- Main Live Command Center (Map & Active Fire Focus) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Interactive Map (Col 8) -->
        <div class="lg:col-span-8 flex flex-col space-y-3">
            <div class="glass-panel p-4 rounded-2xl flex-1 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-rose-500 animate-ping"></div>
                        <h2 class="text-base font-bold text-white tracking-wide">CANLI ORMAN İZLEME VE MÜDAHALE HARİTASI</h2>
                    </div>

                    <!-- Map Layer Controls -->
                    <div class="flex items-center space-x-2 text-xs">
                        <button id="btnFocusFire" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-semibold transition flex items-center space-x-1.5 shadow-lg shadow-rose-600/30">
                            <span>🔥</span>
                            <span>Yangına Odaklan</span>
                        </button>
                        <button id="btnRefreshMap" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold transition border border-slate-700">
                            🔄 Yenile
                        </button>
                    </div>
                </div>

                <!-- Map Container -->
                <div id="silvanusMap" class="w-full h-[520px] rounded-xl overflow-hidden border border-slate-800 relative z-10"></div>

                <!-- Map Legend -->
                <div class="flex flex-wrap items-center justify-between gap-4 mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400">
                    <div class="flex items-center space-x-4">
                        <span class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-rose-500 shadow-sm shadow-rose-500"></span>
                            <span class="text-slate-200">🔥 Yangın (Kritik)</span>
                        </span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500"></span>
                            <span class="text-slate-200">📡 Sensör (Aktif)</span>
                        </span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-cyan-500 shadow-sm shadow-cyan-500"></span>
                            <span class="text-slate-200">📷 Kamera (Gözetleme)</span>
                        </span>
                        <span class="flex items-center space-x-1.5">
                            <span class="w-3 h-3 rounded-full bg-amber-500 shadow-sm shadow-amber-500"></span>
                            <span class="text-slate-200">🏢 İtfaiye Merkezi</span>
                        </span>
                    </div>

                    <div class="text-slate-500">
                        * Koordinatlar sistem tarafından donanım altyapısından otomatik alınır.
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Incident Spotlight & Live Camera Panel (Col 4) -->
        <div class="lg:col-span-4 flex flex-col space-y-4">

            <!-- Canlı Raspberry Pi 5 Kamera Kartı (Her Zaman Görünür) -->
            <div class="glass-panel p-4 rounded-2xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                        <h3 class="text-sm font-bold text-white">Canlı Kamera & YOLOv8 Tespit</h3>
                    </div>
                    <span class="text-[10px] bg-rose-600/90 text-white font-bold px-2 py-0.5 rounded">RPi5 - CANLI</span>
                </div>
                <div class="relative rounded-xl overflow-hidden border border-slate-800 bg-black h-48">
                    <img 
                        src="http://192.168.137.153:5000/video_feed" 
                        alt="Canlı Termal Kamera Akışı" 
                        class="w-full h-full object-cover"
                        crossorigin="anonymous"
                    />
                </div>
            </div>

            <!-- Olay Durumu -->
            @if($latestCritical)
                <div class="glass-panel-glow p-5 rounded-2xl flex-1 flex flex-col justify-between">
                    <div>
                        <!-- Header -->
                        <div class="flex items-center justify-between pb-3 border-b border-rose-500/30">
                            <div>
                                <span class="text-[10px] font-mono uppercase tracking-widest text-rose-400 font-bold">ÖNCELİKLİ OLAY</span>
                                <h3 class="text-lg font-extrabold text-white mt-0.5">{{ $latestCritical->title }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-500 text-white shadow-lg shadow-rose-500/40 animate-pulse">
                                %{{ number_format($latestCritical->risk_score, 0) }} KRİTİK
                            </span>
                        </div>

                        <!-- Incident Meta Tags -->
                        <div class="grid grid-cols-2 gap-2 my-3 text-xs">
                            <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                                <span class="text-slate-500 block text-[10px]">Olay Kodu</span>
                                <span class="font-mono font-bold text-white">{{ $latestCritical->incident_code }}</span>
                            </div>
                            <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                                <span class="text-slate-500 block text-[10px]">Tespit Zamanı</span>
                                <span class="font-mono text-slate-200">{{ $latestCritical->detected_at?->format('H:i:s') }} ({{ $latestCritical->detected_at?->diffForHumans() }})</span>
                            </div>
                            <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800 col-span-2">
                                <span class="text-slate-500 block text-[10px]">Otomatik Belirlenen Konum</span>
                                <span class="font-mono text-amber-400 text-xs font-semibold">📍 Lat: {{ number_format($latestCritical->latitude, 5) }}, Lng: {{ number_format($latestCritical->longitude, 5) }}</span>
                            </div>
                        </div>

                        <!-- Live Telemetry Gauges -->
                        <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 my-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-300">📡 Sensör Verileri (Otomatik Akış)</span>
                                <span class="text-[10px] text-emerald-400 font-mono">SNS-102 (Canlı)</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-center font-mono">
                                <div class="bg-slate-900 p-2 rounded-lg border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block">Sıcaklık</span>
                                    <span class="text-sm font-bold text-rose-400">72°C</span>
                                </div>
                                <div class="bg-slate-900 p-2 rounded-lg border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block">Nem</span>
                                    <span class="text-sm font-bold text-amber-400">%13</span>
                                </div>
                                <div class="bg-slate-900 p-2 rounded-lg border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block">Duman</span>
                                    <span class="text-sm font-bold text-rose-400">92 ppm</span>
                                </div>
                                <div class="bg-slate-900 p-2 rounded-lg border border-slate-800">
                                    <span class="text-[10px] text-slate-500 block">Gaz (CO)</span>
                                    <span class="text-sm font-bold text-orange-400">45 ppm</span>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Fire Station -->
                        <div class="bg-slate-900/80 p-3 rounded-xl border border-slate-800 mb-4 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-base">
                                    🏢
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block">En Yakın Merkez (Haversine)</span>
                                    <span class="font-bold text-white">{{ $latestCritical->fireStation?->name ?? 'Belirleniyor' }}</span>
                                </div>
                            </div>
                            <span class="text-amber-400 font-mono font-bold">2.4 km (6 dk)</span>
                        </div>
                    </div>

                    <!-- Action Link -->
                    <a href="{{ route('incidents.show', $latestCritical->id) }}" class="w-full py-3 rounded-xl bg-gradient-to-r from-rose-600 to-orange-600 hover:from-rose-500 hover:to-orange-500 text-white font-bold text-center text-sm transition shadow-lg shadow-rose-600/30 flex items-center justify-center space-x-2">
                        <span>🔍 Olay Detayını ve Müdahaleyi Yönet</span>
                        <span>→</span>
                    </a>
                </div>
            @else
                <div class="glass-panel p-6 rounded-2xl flex-1 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-2xl mb-2">
                        🌲
                    </div>
                    <h3 class="text-base font-bold text-white">Tüm Sektörler Güvenli</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">Şu anda aktif bir yangın uyarısı bulunmamaktadır. Sensörler ve kameralar normal değerler raporlamaktadır.</p>
                    
                    <a href="{{ route('simulator') }}" class="mt-4 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition">
                        ⚡ Yangın Senaryosu Başlat
                    </a>
                </div>
            @endif

        </div>
    </div>

    <!-- Active Incidents Table & Sensor Telemetry Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Incidents List (Col 7) -->
        <div class="lg:col-span-7 glass-panel p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <h3 class="text-base font-bold text-white">Aktif Yangın ve Erken Tespit Olayları</h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">{{ $activeIncidents->count() }}</span>
                </div>
                <a href="{{ route('incidents.index') }}" class="text-xs font-semibold text-rose-400 hover:text-rose-300">Tümünü Gör →</a>
            </div>

            <div class="divide-y divide-slate-800">
                @forelse($activeIncidents as $inc)
                    <div class="py-3 flex items-center justify-between hover:bg-slate-900/40 px-2 rounded-lg transition">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-lg">
                                🔥
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('incidents.show', $inc->id) }}" class="font-bold text-sm text-white hover:text-rose-400 transition">
                                        {{ $inc->title }}
                                    </a>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $inc->severity->badgeColor() }}">
                                        {{ $inc->severity->label() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    <span class="font-mono text-slate-500">{{ $inc->incident_code }}</span>
                                    <span>{{ $inc->zone_name }}</span>
                                    <span class="text-amber-400 font-medium">{{ $inc->fireStation?->name }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-xs font-bold font-mono text-white block">Durum: {{ $inc->status->label() }}</span>
                            <a href="{{ route('incidents.show', $inc->id) }}" class="text-xs text-rose-400 font-semibold hover:underline">Detay / Müdahale →</a>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-500 text-xs">
                        Aktif yangın kaydı bulunmamaktadır.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Sensor Fleet Telemetry (Col 5) -->
        <div class="lg:col-span-5 glass-panel p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <h3 class="text-base font-bold text-white">Orman Sensörleri Telemetrisi</h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Canlı</span>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ $sensors->count() }} Düğüm</span>
            </div>

            <div class="space-y-2 max-h-[340px] overflow-y-auto pr-1">
                @foreach($sensors->take(8) as $s)
                    <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2.5">
                            @php
                                $statusVal = is_object($s->status ?? null) ? ($s->status->value ?? 'ACTIVE') : ($s->status ?? 'ACTIVE');
                            @endphp
                            <span class="w-2 h-2 rounded-full {{ $statusVal === 'ACTIVE' ? 'bg-emerald-400 animate-pulse' : ($statusVal === 'WARNING' ? 'bg-amber-400' : 'bg-rose-400') }}"></span>
                            <div>
                                <span class="font-bold text-white block">{{ $s->sensor_code ?? 'ESP32 Düğümü #'.$s->id }}</span>
                                <span class="text-[10px] text-slate-500">{{ $s->zone_name ?? 'Sıcaklık: '.$s->temperature.'°C | Nem: %'.$s->humidity }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-mono text-emerald-400 font-bold block">{{ $s->gas_level ?? 0 }} PPM</span>
                            <span class="text-[9px] text-slate-500">{{ $s->created_at ? $s->created_at->diffForHumans() : '' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        // Initialize Leaflet Map
        const map = L.map('silvanusMap', {
            zoomControl: true,
            attributionControl: false
        }).setView([39.6500, 26.8500], 11);

        // OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            subdomains: 'abc'
        }).addTo(map);

        setTimeout(() => {
            map.invalidateSize();
        }, 500);

        // Custom DivIcons
        const createFireIcon = (risk) => L.divIcon({
            className: 'custom-fire-marker',
            html: `
                <div class="relative flex items-center justify-center">
                    <div class="w-10 h-10 rounded-full bg-rose-600/30 fire-marker-pulse absolute"></div>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-rose-600 to-orange-500 border-2 border-white shadow-xl flex items-center justify-center text-sm relative z-10 cursor-pointer">
                        🔥
                    </div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        const sensorIcon = L.divIcon({
            className: 'custom-sensor-marker',
            html: `
                <div class="w-6 h-6 rounded-full bg-emerald-600 border border-white/80 shadow-md flex items-center justify-center text-[11px] text-white font-bold cursor-pointer hover:scale-110 transition">
                    📡
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        const cameraIcon = L.divIcon({
            className: 'custom-cam-marker',
            html: `
                <div class="w-6 h-6 rounded-full bg-cyan-600 border border-white/80 shadow-md flex items-center justify-center text-[11px] text-white font-bold cursor-pointer hover:scale-110 transition">
                    📷
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        const stationIcon = L.divIcon({
            className: 'custom-station-marker',
            html: `
                <div class="w-8 h-8 rounded-xl bg-amber-600 border-2 border-white shadow-lg flex items-center justify-center text-sm text-white font-bold cursor-pointer hover:scale-110 transition">
                    🏢
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        let fireLatLng = null;

        // Fetch Live Map Elements from API
        async function loadMapElements() {
            try {
                const response = await fetch('/api/v1/map/elements');
                const json = await response.json();
                if (!json.success) return;

                const { fires, sensors, cameras, stations } = json.data;

                // Render Stations
                stations?.forEach(st => {
                    const marker = L.marker([st.latitude, st.longitude], { icon: stationIcon }).addTo(map);
                    marker.bindPopup(`
                        <div class="p-2 space-y-1">
                            <span class="text-[10px] font-bold text-amber-400 uppercase">🏢 İtfaiye Merkezi</span>
                            <h4 class="font-bold text-sm text-white">${st.name}</h4>
                            <p class="text-xs text-slate-300">Kapsama: ${st.coverage_radius_km} km | Birimler: ${st.active_units_count}</p>
                        </div>
                    `);
                });

                // Render Sensors
                sensors?.forEach(sn => {
                    const marker = L.marker([sn.latitude, sn.longitude], { icon: sensorIcon }).addTo(map);
                    marker.bindPopup(`
                        <div class="p-2 space-y-1 font-mono text-xs">
                            <span class="text-[10px] font-bold text-emerald-400 uppercase">📡 Orman Sensörü</span>
                            <h4 class="font-bold text-sm text-white font-sans">${sn.name} (${sn.code})</h4>
                        </div>
                    `);
                });

                // Render Cameras
                cameras?.forEach(cm => {
                    const marker = L.marker([cm.latitude, cm.longitude], { icon: cameraIcon }).addTo(map);
                    marker.bindPopup(`
                        <div class="p-2 space-y-1 text-xs">
                            <span class="text-[10px] font-bold text-cyan-400 uppercase">📷 Termal/Optik Kamera</span>
                            <h4 class="font-bold text-sm text-white">${cm.name} (${cm.code})</h4>
                        </div>
                    `);
                });

                // Render Fires
                fires?.forEach(fr => {
                    fireLatLng = [fr.latitude, fr.longitude];
                    const marker = L.marker([fr.latitude, fr.longitude], { icon: createFireIcon(fr.risk_score) }).addTo(map);
                    marker.bindPopup(`
                        <div class="p-2 space-y-1.5">
                            <span class="text-[10px] font-bold text-rose-400 uppercase tracking-wider">🚨 KRİTİK YANGIN TESPİTİ</span>
                            <h4 class="font-bold text-sm text-white">${fr.title}</h4>
                        </div>
                    `);
                    map.setView([fr.latitude, fr.longitude], 12, { animate: true });
                });

                map.invalidateSize();
            } catch (err) {
                console.error('Harita yükleme hatası:', err);
            }
        }

        await loadMapElements();

        // Focus button listener
        document.getElementById('btnFocusFire')?.addEventListener('click', () => {
            if (fireLatLng) {
                map.flyTo(fireLatLng, 13, { duration: 1.5 });
            } else {
                map.flyTo([39.6500, 26.8500], 11);
            }
        });

        document.getElementById('btnRefreshMap')?.addEventListener('click', () => {
            loadMapElements();
            map.invalidateSize();
        });
    });
</script>
@endpush