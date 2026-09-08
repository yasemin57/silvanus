@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">Orman Yangını Olayları & Geçmiş Müdahaleler</h1>
            <p class="text-xs text-slate-400 mt-1">Sistem tarafından otomatik tespit edilen tüm yangınlar, müdahale süreleri ve operasyonel kayıtlar.</p>
        </div>

        <a href="{{ route('simulator') }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold text-xs transition shadow-lg shadow-amber-600/30 flex items-center space-x-2">
            <span>⚡ Yeni Yangın Simülasyonu Başlat</span>
        </a>
    </div>

    <!-- Table Card -->
    <div class="glass-panel p-5 rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-800 font-mono">
                    <tr>
                        <th class="py-3 px-3">Olay Kodu</th>
                        <th class="py-3 px-3">Bölge & Başlık</th>
                        <th class="py-3 px-3">Risk & Seviye</th>
                        <th class="py-3 px-3">Durum</th>
                        <th class="py-3 px-3">Atanan Merkez</th>
                        <th class="py-3 px-3">Tespit Zamanı</th>
                        <th class="py-3 px-3 text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($incidents as $inc)
                        <tr class="hover:bg-slate-900/50 transition">
                            <td class="py-3 px-3 font-mono font-bold text-white">{{ $inc->incident_code }}</td>
                            <td class="py-3 px-3">
                                <a href="{{ route('incidents.show', $inc->id) }}" class="font-semibold text-white hover:text-rose-400">
                                    {{ $inc->title }}
                                </a>
                                <span class="block text-[10px] text-slate-500 font-mono">📍 {{ number_format($inc->latitude, 4) }}, {{ number_format($inc->longitude, 4) }}</span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $inc->severity->badgeColor() }}">
                                    %{{ number_format($inc->risk_score, 0) }} {{ $inc->severity->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-slate-800 text-slate-200 border border-slate-700">
                                    {{ $inc->status->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-300">
                                {{ $inc->fireStation?->name ?? '-' }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-400">
                                {{ $inc->detected_at?->format('d.m.Y H:i:s') }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('incidents.show', $inc->id) }}" class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] transition">
                                    İncele →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">Kayıtlı olay bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-800">
            {{ $incidents->links() }}
        </div>
    </div>

</div>
@endsection
