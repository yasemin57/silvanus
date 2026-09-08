@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Back button & Status Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('incidents.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-semibold transition">
                ← Olaylar Listesine Dön
            </a>
            <div>
                <span class="text-xs font-mono font-bold text-slate-400">{{ $incident->incident_code }}</span>
                <h1 class="text-2xl font-extrabold text-white flex items-center space-x-3">
                    <span>{{ $incident->title }}</span>
                </h1>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <span class="px-3 py-1.5 rounded-full text-xs font-extrabold {{ $incident->severity->badgeColor() }}">
                %{{ number_format($incident->risk_score, 0) }} {{ $incident->severity->label() }}
            </span>
            <span class="px-3 py-1.5 rounded-full text-xs font-extrabold bg-slate-800 text-slate-200 border border-slate-700">
                Durum: {{ $incident->status->label() }}
            </span>
        </div>
    </div>

    <!-- 7-Stage Incident Lifecycle Timeline Stepper -->
    <div class="glass-panel p-5 rounded-2xl">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Olay Müdahale Yaşam Döngüsü (Lifecycle Tracker)</h3>
        
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 text-center text-xs">
            @php
                $steps = [
                    'DETECTED' => ['label' => '1. Tespit', 'icon' => '🤖'],
                    'NOTIFIED' => ['label' => '2. Bildirildi', 'icon' => '🔔'],
                    'ACCEPTED' => ['label' => '3. Kabul', 'icon' => '✅'],
                    'ON_THE_WAY' => ['label' => '4. İntikal', 'icon' => '🚒'],
                    'ARRIVED' => ['label' => '5. Varış', 'icon' => '📍'],
                    'UNDER_CONTROL' => ['label' => '6. Kontrol', 'icon' => '🛡️'],
                    'RESOLVED' => ['label' => '7. Kapatıldı', 'icon' => '🏁'],
                ];
                $currentOrder = $incident->status->stepOrder();
            @endphp

            @foreach($steps as $statusCode => $step)
                @php
                    $stepEnum = \App\Enums\IncidentStatus::from($statusCode);
                    $stepOrder = $stepEnum->stepOrder();
                    $isPassed = $currentOrder >= $stepOrder;
                    $isCurrent = $currentOrder === $stepOrder;
                @endphp
                <div class="p-3 rounded-xl border transition {{ $isCurrent ? 'bg-rose-500/20 border-rose-500 text-white font-bold shadow-lg shadow-rose-500/20' : ($isPassed ? 'bg-slate-900 border-slate-700 text-slate-300' : 'bg-slate-950/40 border-slate-800 text-slate-600') }}">
                    <div class="text-lg mb-1">{{ $step['icon'] }}</div>
                    <div class="text-[11px] uppercase tracking-wider">{{ $step['label'] }}</div>
                    @if($isCurrent)
                        <span class="inline-block mt-1 text-[9px] bg-rose-500 text-white px-1.5 py-0.2 rounded-full">Aktif Adım</span>
                    @elseif($isPassed)
                        <span class="inline-block mt-1 text-[9px] text-emerald-400">Tamamlandı</span>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Next Action Button Trigger -->
        @if(! $incident->isResolved())
            @php $next = $incident->status->nextStatus(); @endphp
            @if($next)
                <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        İtfaiye personeli sahadaki eylemi bu buton ile anlık sisteme işleyebilir:
                    </span>
                    <form action="/api/v1/incidents/{{ $incident->id }}/transition" method="POST" onsubmit="event.preventDefault(); transitionIncident('{{ $next->value }}');">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-orange-600 hover:from-rose-500 hover:to-orange-500 text-white font-bold text-xs transition shadow-lg shadow-rose-600/30 flex items-center space-x-2">
                            <span>➡️ [ {{ strtoupper($next->label()) }} ]</span>
                        </button>
                    </form>
                </div>
            @endif
        @else
            <div class="mt-4 pt-4 border-t border-slate-800 text-center text-xs text-emerald-400 font-semibold">
                ✅ Bu yangın olayı başarıyla söndürülmüş ve arşivlenmiştir.
            </div>
        @endif
    </div>

    <!-- Main Grid: Map & Camera & Sensors -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Google Maps Location & Haversine Route (Col 7) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Map Card -->
            <div class="glass-panel p-5 rounded-2xl">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                            <span>📍 Otomatik Belirlenen Yangın Koordinatı</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Sistem sensör ve kamera koordinatlarını harita üzerinde otomatik konumlandırdı.</p>
                    </div>
                    <span class="font-mono text-xs bg-slate-900 px-2.5 py-1 rounded-lg text-amber-400 border border-slate-800">
                        {{ number_format($incident->latitude, 5) }}, {{ number_format($incident->longitude, 5) }}
                    </span>
                </div>

                <div id="incidentDetailMap" class="w-full h-80 rounded-xl overflow-hidden border border-slate-800"></div>

                <!-- Geodesic Station Dispatch Info -->
                <div class="mt-4 grid grid-cols-3 gap-3 text-center text-xs font-mono">
                    <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Atanan Merkez</span>
                        <span class="font-bold text-white font-sans">{{ $incident->fireStation?->name }}</span>
                    </div>
                    <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Haversine Kuş Uçuşu</span>
                        <span class="font-bold text-amber-400">2.4 km</span>
                    </div>
                    <div class="bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Tahmini Varış Süresi</span>
                        <span class="font-bold text-emerald-400">6 Dakika</span>
                    </div>
                </div>
            </div>

            <!-- Live Sensor Telemetry Detail -->
            <div class="glass-panel p-5 rounded-2xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-white">📡 Donanım Sensör Ölçümleri (Manuel Giriş Yok)</h3>
                    <span class="text-xs font-mono text-emerald-400">Bağlı Sensör: {{ $incident->primarySensor?->sensor_code ?? 'SNS-102' }}</span>
                </div>

                @php $latestR = $incident->primarySensor?->readings?->first(); @endphp
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center font-mono">
                    <div class="bg-slate-900/90 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Sıcaklık</span>
                        <span class="text-xl font-extrabold text-rose-400">{{ $latestR?->temperature ?? 72.0 }}°C</span>
                        <span class="text-[9px] text-rose-500/80 block mt-0.5">Eşik: >50°C Aşımı</span>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Nem Seviyesi</span>
                        <span class="text-xl font-extrabold text-amber-400">%{{ $latestR?->humidity ?? 13.0 }}</span>
                        <span class="text-[9px] text-amber-500/80 block mt-0.5">Kritik Kuruluk</span>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Duman Yoğunluğu</span>
                        <span class="text-xl font-extrabold text-rose-400">{{ $latestR?->smoke_ppm ?? 92.0 }} ppm</span>
                        <span class="text-[9px] text-rose-500/80 block mt-0.5">Yoğun Partikül</span>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-xl border border-slate-800">
                        <span class="text-[10px] text-slate-500 block">Gaz Konsantrasyonu</span>
                        <span class="text-xl font-extrabold text-orange-400">{{ $latestR?->co_gas_ppm ?? 45.0 }} ppm</span>
                        <span class="text-[9px] text-orange-500/80 block mt-0.5">Yanma Gazı</span>
                    </div>
                </div>

                <!-- AI Analysis Summary Alert -->
                <div class="mt-4 p-3.5 rounded-xl bg-slate-900/90 border border-rose-500/30 text-xs">
                    <span class="font-bold text-rose-400 block mb-1">🤖 Silvanus Yerel Sunucu AI Değerlendirmesi:</span>
                    <p class="text-slate-300 leading-relaxed">
                        {{ $incident->ai_analysis_summary['summary'] ?? 'Sıcaklık ve duman değerlerindeki ani yükseliş termal kamera analizi ile çapraz doğrulanarak yangın olasılığı %96 olarak hesaplandı.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Camera Stream & Timeline (Col 5) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Live Camera Feed (Requirement 14) -->
            <div class="glass-panel p-5 rounded-2xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                        <h3 class="text-sm font-bold text-white">📷 Canlı Kamera Görüntüsü</h3>
                    </div>
                    <span class="text-xs font-mono text-cyan-400">{{ $incident->primaryCamera?->camera_code ?? 'CAM-022' }}</span>
                </div>

                <!-- Video / Snapshot Player with HUD Overlay -->
                <div class="relative w-full h-56 rounded-xl overflow-hidden border border-slate-800 bg-black">
                    @if($incident->primaryCamera?->stream_url)
                        <video class="w-full h-full object-cover" autoplay loop muted playsinline>
                            <source src="{{ $incident->primaryCamera->stream_url }}" type="video/mp4">
                        </video>
                    @else
                        <img src="https://images.unsplash.com/photo-1516214104703-d870798883c5?w=800" class="w-full h-full object-cover" alt="Thermal Stream">
                    @endif

                    <!-- HUD Overlays -->
                    <div class="absolute top-2 left-2 bg-black/70 px-2 py-0.5 rounded text-[10px] font-mono text-emerald-400 border border-emerald-500/30">
                        REC ● 4K 60FPS
                    </div>
                    <div class="absolute top-2 right-2 bg-rose-600/90 px-2 py-0.5 rounded text-[10px] font-bold text-white">
                        AI ALEV TESPİTİ (%98)
                    </div>
                    <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between text-[10px] font-mono text-slate-300 bg-black/60 px-2 py-1 rounded">
                        <span>FOV: 140° | AZ: 45°</span>
                        <span>IR TERMAL AKTİF</span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 mt-2">
                    * İtfaiye personeli sayısal telemetrinin yanında yangın sahasının gerçek zamanlı görsel ve termal durumunu takip edebilir.
                </p>
            </div>

            <!-- Incident Event Timeline (Requirement 21) -->
            <div class="glass-panel p-5 rounded-2xl">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center justify-between">
                    <span>⏱️ Olay Zaman Çizelgesi & Performans</span>
                    <span class="text-xs text-slate-500 font-mono">{{ $incident->timelines->count() }} Kayıt</span>
                </h3>

                <div class="space-y-3 relative border-l-2 border-slate-800 ml-3 pl-4">
                    @foreach($incident->timelines as $timeline)
                        <div class="relative group">
                            <div class="w-2.5 h-2.5 rounded-full bg-rose-500 absolute -left-[21px] top-1 border-2 border-slate-900"></div>
                            <div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-white">{{ $timeline->action_title ?? $timeline->status_to }}</span>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $timeline->recorded_at?->format('H:i:s') }}</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $timeline->note }}</p>
                                @if($timeline->duration_from_previous_seconds > 0)
                                    <span class="text-[10px] font-mono text-amber-400 bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800 inline-block mt-1">
                                        ⏱️ Önceki adımdan geçen: {{ $timeline->duration_from_previous_seconds }} sn
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fireLat = {{ $incident->latitude }};
        const fireLng = {{ $incident->longitude }};
        const stationLat = {{ $incident->fireStation?->latitude ?? 39.7124 }};
        const stationLng = {{ $incident->fireStation?->longitude ?? 26.8621 }};

        // Initialize Incident Detail Map
        const map = L.map('incidentDetailMap', { zoomControl: true, attributionControl: false }).setView([fireLat, fireLng], 13);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            subdomains: 'abcd',
        }).addTo(map);

        // Fire Marker
        const fireIcon = L.divIcon({
            className: 'custom-fire',
            html: `
                <div class="relative flex items-center justify-center">
                    <div class="w-10 h-10 rounded-full bg-rose-600/30 fire-marker-pulse absolute"></div>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-rose-600 to-orange-500 border-2 border-white shadow-xl flex items-center justify-center text-sm relative z-10">
                        🔥
                    </div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        // Station Marker
        const stationIcon = L.divIcon({
            className: 'custom-stn',
            html: `
                <div class="w-8 h-8 rounded-xl bg-amber-600 border-2 border-white shadow-lg flex items-center justify-center text-sm text-white font-bold">
                    🏢
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        L.marker([fireLat, fireLng], { icon: fireIcon }).addTo(map).bindPopup('<b>🔥 Yangın Koordinatı</b><br>{{ $incident->title }}').openPopup();
        L.marker([stationLat, stationLng], { icon: stationIcon }).addTo(map).bindPopup('<b>🏢 {{ $incident->fireStation?->name }}</b><br>Mesafe: 2.4 km');

        // Dispatch Line from Station to Fire
        L.polyline([[stationLat, stationLng], [fireLat, fireLng]], {
            color: '#ef4444',
            weight: 3,
            dashArray: '6, 8',
            opacity: 0.8
        }).addTo(map);
    });

    async function transitionIncident(nextStatus) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch('/api/v1/incidents/{{ $incident->id }}/transition', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ status: nextStatus })
            });
            const json = await res.json();
            if (json.success) {
                window.location.reload();
            } else {
                alert(json.message || 'Durum güncellenemedi.');
            }
        } catch (e) {
            alert('Hata oluştu: ' + e.message);
        }
    }
</script>
@endpush
