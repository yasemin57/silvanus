<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Api\EdgeTelemetryController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TriggerWildfireScenarioCommand extends Command
{
    protected $signature = 'silvanus:trigger-wildfire {--zone=Silvanus Bölgesi 03 : Zone name} {--temp=78.5 : Temperature} {--smoke=95.0 : Smoke PPM}';

    protected $description = 'Trigger a critical wildfire scenario via Edge AI analysis';

    public function handle(EdgeTelemetryController $edgeController): int
    {
        $zone = (string) $this->option('zone');
        $temp = (float) $this->option('temp');
        $smoke = (float) $this->option('smoke');

        $this->alert("🚨 SILVANUS YANGIN SENARYOSU BAŞLATILIYOR: {$zone}");

        $request = Request::create('/api/v1/edge/trigger-incident', 'POST', [
            'zone_name' => $zone,
            'temperature' => $temp,
            'humidity' => 11.5,
            'smoke_ppm' => $smoke,
            'co_gas_ppm' => 52.0,
            'vision_confidence' => 98.4,
            'flame_detected' => true,
        ]);

        $response = $edgeController->triggerIncident($request);
        $data = $response->getData(true);

        if ($data['success'] ?? false) {
            $incident = $data['incident'];
            $this->info("🔥 Olay Kodu: {$incident['incident_code']}");
            $this->info("📍 Konum: Lat {$incident['latitude']}, Lng {$incident['longitude']}");
            $this->info("📊 Risk Skoru: %{$incident['risk_score']} ({$incident['severity']})");
            $this->info("🏢 Atanan Merkez: {$incident['fire_station']['name']}");
            $this->info('🔔 Push Notification gönderildi!');

            return self::SUCCESS;
        }

        $this->error('Yangın senaryosu oluşturulamadı.');

        return self::FAILURE;
    }
}
