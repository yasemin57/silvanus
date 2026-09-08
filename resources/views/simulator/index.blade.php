@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">OPERATÖR TEST ALTYAPISI</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Silvanus Edge AI ve Yangın Simülatörü</h1>
            <p class="text-xs text-slate-400 mt-1">Donanım ve sensör akışlarını, yapay zekâ tespitini ve itfaiye push notification mekanizmasını uçtan uca simüle edin.</p>
        </div>

        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs border border-slate-800 transition">
            🗺️ Canlı Haritayı Aç
        </a>
    </div>

    <!-- Simulator Presets & Custom Trigger Form -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Form (Col 7) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- 1-Click Quick Scenarios -->
            <div class="glass-panel p-5 rounded-2xl">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">⚡ Hızlı Test Senaryoları (1-Tık)</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button onclick="applyPreset('critical')" class="p-3.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-left transition group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-rose-400">🔥 Kritik Yangın (96% Risk)</span>
                            <span class="text-xs group-hover:translate-x-1 transition">→</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">78°C Sıcaklık, %11 Nem, 95 ppm Duman, AI Alev Tespiti (%98).</p>
                    </button>

                    <button onclick="applyPreset('early_smoke')" class="p-3.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-left transition group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-amber-400">💨 Erken Duman Başlangıcı</span>
                            <span class="text-xs group-hover:translate-x-1 transition">→</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">52°C Sıcaklık, %24 Nem, 65 ppm Duman, AI Duman Tespiti (%82).</p>
                    </button>
                </div>
            </div>

            <!-- Custom Parameter Form -->
            <div class="glass-panel p-5 rounded-2xl">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">🎛️ Özel Telemetri ve Yapay Zekâ Parametreleri</h3>

                <form id="simulatorForm" onsubmit="event.preventDefault(); submitSimulation();" class="space-y-4">
                    
                    <!-- Region & Hardware Selector -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-slate-400 font-semibold mb-1">Orman Sektörü / Sensör</label>
                            <select id="simSensorId" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:outline-none focus:border-rose-500">
                                @foreach($sensors as $sensor)
                                    <option value="{{ $sensor->id }}">{{ $sensor->sensor_code }} - {{ $sensor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-400 font-semibold mb-1">Eşleşen Gözetleme Kamerası</label>
                            <select id="simCameraId" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:outline-none focus:border-rose-500">
                                @foreach($cameras as $camera)
                                    <option value="{{ $camera->id }}">{{ $camera->camera_code }} - {{ $camera->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Sensor Sliders -->
                    <div class="space-y-3 pt-2">
                        <!-- Temperature -->
                        <div>
                            <div class="flex justify-between text-xs font-mono mb-1">
                                <span class="text-slate-400">Sıcaklık (°C)</span>
                                <span id="valTemp" class="text-rose-400 font-bold">75.0 °C</span>
                            </div>
                            <input type="range" id="simTemp" min="20" max="110" step="0.5" value="75.0" class="w-full accent-rose-500 bg-slate-900 rounded-lg" oninput="document.getElementById('valTemp').innerText = this.value + ' °C'">
                        </div>

                        <!-- Humidity -->
                        <div>
                            <div class="flex justify-between text-xs font-mono mb-1">
                                <span class="text-slate-400">Bağıl Nem (%)</span>
                                <span id="valHumidity" class="text-cyan-400 font-bold">%12.0</span>
                            </div>
                            <input type="range" id="simHumidity" min="5" max="90" step="0.5" value="12.0" class="w-full accent-cyan-500 bg-slate-900 rounded-lg" oninput="document.getElementById('valHumidity').innerText = '%' + this.value">
                        </div>

                        <!-- Smoke PPM -->
                        <div>
                            <div class="flex justify-between text-xs font-mono mb-1">
                                <span class="text-slate-400">Duman Konsantrasyonu (PPM)</span>
                                <span id="valSmoke" class="text-amber-400 font-bold">92.0 PPM</span>
                            </div>
                            <input type="range" id="simSmoke" min="0" max="150" step="1" value="92.0" class="w-full accent-amber-500 bg-slate-900 rounded-lg" oninput="document.getElementById('valSmoke').innerText = this.value + ' PPM'">
                        </div>

                        <!-- CO Gas PPM -->
                        <div>
                            <div class="flex justify-between text-xs font-mono mb-1">
                                <span class="text-slate-400">Yanma Gazı CO (PPM)</span>
                                <span id="valGas" class="text-orange-400 font-bold">45.0 PPM</span>
                            </div>
                            <input type="range" id="simGas" min="0" max="100" step="1" value="45.0" class="w-full accent-orange-500 bg-slate-900 rounded-lg" oninput="document.getElementById('valGas').innerText = this.value + ' PPM'">
                        </div>
                    </div>

                    <!-- AI Vision Options -->
                    <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
                        <label class="flex items-center space-x-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" id="simFlame" checked class="w-4 h-4 accent-rose-500 rounded bg-slate-900">
                            <span>Görsel AI Alev/Duman Tespiti (%98 Güven)</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="btnSubmitSim" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-rose-600 via-orange-600 to-amber-500 hover:from-rose-500 hover:to-amber-400 text-white font-extrabold text-sm transition shadow-lg shadow-rose-600/30 flex items-center justify-center space-x-2">
                        <span>🔥 Yangın Alarmını Tetikle ve İtfaiyeye Bildir</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Live Simulation Output & Response (Col 5) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="glass-panel p-5 rounded-2xl h-full flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">📡 Simülasyon Çıktısı & Sevk Yanıtı</h3>

                    <!-- Status Box -->
                    <div id="simStatusBox" class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-xs font-mono text-slate-400 space-y-2 min-h-[300px]">
                        <p class="text-slate-500">// Silvanus Edge Telemetry & AI Dispatch Log</p>
                        <p class="text-emerald-400">> Yerel sunucu telemetri dinleyicisi hazır.</p>
                        <p class="text-slate-400">> Bir senaryo seçip tetikleyin...</p>
                    </div>
                </div>

                <div id="simActionRow" class="hidden mt-4 pt-4 border-t border-slate-800 flex items-center justify-between">
                    <span class="text-xs text-emerald-400 font-bold">✅ Olay Oluşturuldu</span>
                    <a id="simViewIncidentBtn" href="#" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition">
                        Olay Detayına Git →
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    function applyPreset(type) {
        if (type === 'critical') {
            document.getElementById('simTemp').value = 78.5;
            document.getElementById('valTemp').innerText = '78.5 °C';
            document.getElementById('simHumidity').value = 11.5;
            document.getElementById('valHumidity').innerText = '%11.5';
            document.getElementById('simSmoke').value = 95;
            document.getElementById('valSmoke').innerText = '95 PPM';
            document.getElementById('simGas').value = 52;
            document.getElementById('valGas').innerText = '52 PPM';
            document.getElementById('simFlame').checked = true;
        } else if (type === 'early_smoke') {
            document.getElementById('simTemp').value = 52.0;
            document.getElementById('valTemp').innerText = '52.0 °C';
            document.getElementById('simHumidity').value = 24.0;
            document.getElementById('valHumidity').innerText = '%24.0';
            document.getElementById('simSmoke').value = 65;
            document.getElementById('valSmoke').innerText = '65 PPM';
            document.getElementById('simGas').value = 28;
            document.getElementById('valGas').innerText = '28 PPM';
            document.getElementById('simFlame').checked = false;
        }
    }

    async function submitSimulation() {
        const btn = document.getElementById('btnSubmitSim');
        const statusBox = document.getElementById('simStatusBox');
        const actionRow = document.getElementById('simActionRow');
        const viewBtn = document.getElementById('simViewIncidentBtn');

        btn.disabled = true;
        btn.innerText = 'İşleniyor (AI Analizi & Sevk)...';

        statusBox.innerHTML = `
            <p class="text-amber-400">> [1] Sensör ve kamera telemetrisi Edge sunucuya iletildi...</p>
            <p class="text-cyan-400">> [2] AI Multi-Factor Risk Analizi çalıştırılıyor...</p>
        `;

        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const payload = {
                sensor_id: document.getElementById('simSensorId').value,
                camera_id: document.getElementById('simCameraId').value,
                temperature: parseFloat(document.getElementById('simTemp').value),
                humidity: parseFloat(document.getElementById('simHumidity').value),
                smoke_ppm: parseFloat(document.getElementById('simSmoke').value),
                co_gas_ppm: parseFloat(document.getElementById('simGas').value),
                vision_confidence: document.getElementById('simFlame').checked ? 98.0 : 40.0,
                flame_detected: document.getElementById('simFlame').checked
            };

            const res = await fetch('/api/v1/edge/trigger-incident', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();

            if (json.success) {
                const inc = json.incident;
                statusBox.innerHTML = `
                    <p class="text-rose-400 font-bold">> 🚨 YANGIN TESPİT EDİLDİ: ${inc.incident_code}</p>
                    <p class="text-slate-300">> Bölge: ${inc.zone_name}</p>
                    <p class="text-amber-400">> Risk Skoru: %${inc.risk_score} (${inc.severity})</p>
                    <p class="text-cyan-400">> Otomatik Koordinatlar: ${inc.latitude}, ${inc.longitude}</p>
                    <p class="text-emerald-400">> En Yakın İstasyon: ${inc.fire_station?.name || 'Kazdağları'}</p>
                    <p class="text-slate-400">> 🔔 Push Notification itfaiye personellerine başarıyla gönderildi.</p>
                    <p class="text-slate-500">> Durum: ${inc.status}</p>
                `;

                actionRow.classList.remove('hidden');
                viewBtn.href = `/incidents/${inc.id}`;
            } else {
                statusBox.innerHTML += `<p class="text-rose-500">> HATA: ${json.message}</p>`;
            }

        } catch (e) {
            statusBox.innerHTML += `<p class="text-rose-500">> BAĞLANTI HATASI: ${e.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = '🔥 Yangın Alarmını Tetikle ve İtfaiyeye Bildir';
        }
    }
</script>
@endpush
