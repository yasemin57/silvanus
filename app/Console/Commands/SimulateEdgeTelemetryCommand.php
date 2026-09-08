<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DeviceStatus;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Services\AI\FireRiskAnalysisService;
use App\Services\Dispatch\FireStationLocatorService;
use App\Services\Notifications\FirePushNotificationService;
use Illuminate\Console\Command;

class SimulateEdgeTelemetryCommand extends Command
{
    protected $signature = 'silvanus:simulate-edge {--iterations=1 : Number of simulation cycles} {--interval=2 : Seconds between cycles}';

    protected $description = 'Simulate Edge Server real-time telemetry ingestion and AI inference from forest sensors';

    public function handle(
        FireRiskAnalysisService $riskService,
        FireStationLocatorService $locatorService,
        FirePushNotificationService $pushService
    ): int {
        $iterations = (int) $this->option('iterations');
        $interval = (int) $this->option('interval');

        $this->info('🌲 [SILVANUS EDGE DAEMON] Yerel Sunucu Telemetri Motoru Başlatıldı.');
        $this->line('Sensör ve kamera verileri toplanıyor, yapay zekâ çıkarımı yapılıyor...');

        $sensors = Sensor::query()->where('status', '!=', DeviceStatus::Offline)->get();

        if ($sensors->isEmpty()) {
            $this->warn('Aktif sensör bulunamadı. Lütfen önce seeder çalıştırın.');

            return self::FAILURE;
        }

        for ($cycle = 1; $cycle <= $iterations; $cycle++) {
            $this->info(sprintf('--- Döngü %d/%d [%s] ---', $cycle, $iterations, now()->format('H:i:s')));

            foreach ($sensors as $sensor) {
                // Generate slight ambient fluctuation
                $baseTemp = 24.0 + (float) (sin(time() / 60) * 3);
                $temp = round($baseTemp + (random_int(-10, 10) / 10.0), 2);
                $humidity = round(55.0 + (random_int(-15, 15) / 10.0), 2);
                $smoke = round(8.0 + (random_int(0, 40) / 10.0), 2);
                $co = round(4.0 + (random_int(0, 20) / 10.0), 2);

                SensorReading::query()->create([
                    'sensor_id' => $sensor->id,
                    'temperature' => $temp,
                    'humidity' => $humidity,
                    'smoke_ppm' => $smoke,
                    'co_gas_ppm' => $co,
                    'air_quality_index' => (int) ($smoke * 2.5),
                    'recorded_at' => now(),
                ]);

                $sensor->update(['last_ping_at' => now()]);

                $eval = $riskService->evaluate($temp, $humidity, $smoke, $co);
                $this->line(sprintf('📡 %s: %.1f°C | Nem: %%%.1f | Duman: %.1f ppm -> AI Risk: %%%.1f (%s)',
                    $sensor->sensor_code,
                    $temp,
                    $humidity,
                    $smoke,
                    $eval['risk_score'],
                    $eval['severity']->label()
                ));
            }

            if ($cycle < $iterations) {
                sleep($interval);
            }
        }

        $this->info('✅ Edge telemetri simülasyon döngüsü tamamlandı.');

        return self::SUCCESS;
    }
}
