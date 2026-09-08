<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\IncidentSeverity;
use App\Models\Camera;
use App\Models\Sensor;

class FireRiskAnalysisService
{
    /**
     * Evaluate real-time sensor metrics and AI camera vision detection.
     *
     * @param  array<string, mixed>  $visionData
     * @return array{
     *     risk_score: float,
     *     severity: IncidentSeverity,
     *     should_trigger_alarm: bool,
     *     ai_confidence: float,
     *     breakdown: array<string, mixed>,
     *     summary: string
     * }
     */
    public function evaluate(
        float $temperature,
        float $humidity,
        float $smokePpm,
        float $coGasPpm,
        float $visionConfidence = 0.0,
        bool $flameDetected = false,
        bool $smokeDetected = false
    ): array {
        // 1. Temperature score (0 - 30 pts)
        // Baseline normal ~25°C, high risk starts above 45°C, extreme > 70°C
        $tempScore = 0.0;
        if ($temperature > 70) {
            $tempScore = 30.0;
        } elseif ($temperature > 50) {
            $tempScore = 20.0 + (($temperature - 50) / 20.0) * 10.0;
        } elseif ($temperature > 38) {
            $tempScore = 10.0 + (($temperature - 38) / 12.0) * 10.0;
        }

        // 2. Humidity score (0 - 20 pts)
        // Normal > 45%, severe drought / fire propensity < 18%
        $humidityScore = 0.0;
        if ($humidity < 15) {
            $humidityScore = 20.0;
        } elseif ($humidity < 25) {
            $humidityScore = 12.0 + ((25 - $humidity) / 10.0) * 8.0;
        } elseif ($humidity < 35) {
            $humidityScore = 5.0 + ((35 - $humidity) / 10.0) * 7.0;
        }

        // 3. Smoke PPM score (0 - 25 pts)
        // Normal < 15 ppm, critical > 80 ppm
        $smokeScore = 0.0;
        if ($smokePpm > 80) {
            $smokeScore = 25.0;
        } elseif ($smokePpm > 40) {
            $smokeScore = 15.0 + (($smokePpm - 40) / 40.0) * 10.0;
        } elseif ($smokePpm > 20) {
            $smokeScore = 5.0 + (($smokePpm - 20) / 20.0) * 10.0;
        }

        // 4. CO Gas PPM score (0 - 15 pts)
        // Normal < 10 ppm, fire combustion > 35 ppm
        $gasScore = 0.0;
        if ($coGasPpm > 35) {
            $gasScore = 15.0;
        } elseif ($coGasPpm > 20) {
            $gasScore = 8.0 + (($coGasPpm - 20) / 15.0) * 7.0;
        } elseif ($coGasPpm > 10) {
            $gasScore = 3.0 + (($coGasPpm - 10) / 10.0) * 5.0;
        }

        // Sensor subtotal (out of 90 max, normalized)
        $sensorRawTotal = $tempScore + $humidityScore + $smokeScore + $gasScore;
        $sensorNormalized = min(100.0, ($sensorRawTotal / 90.0) * 100.0);

        // 5. Vision AI fusion (Weight: 40% Vision + 60% Multi-sensor)
        $visionScore = 0.0;
        if ($flameDetected) {
            $visionScore = max($visionConfidence, 85.0);
        } elseif ($smokeDetected) {
            $visionScore = max($visionConfidence, 65.0);
        } else {
            $visionScore = $visionConfidence;
        }

        if ($visionScore > 0) {
            $compoundRisk = ($sensorNormalized * 0.55) + ($visionScore * 0.45);
        } else {
            $compoundRisk = $sensorNormalized;
        }

        $compoundRisk = round(min(99.9, max(0.0, $compoundRisk)), 2);

        // Determine severity
        $severity = match (true) {
            $compoundRisk >= 85.0 => IncidentSeverity::Critical,
            $compoundRisk >= 65.0 => IncidentSeverity::High,
            $compoundRisk >= 40.0 => IncidentSeverity::Medium,
            default => IncidentSeverity::Low,
        };

        $shouldTriggerAlarm = $compoundRisk >= 65.0;
        $aiConfidence = round(min(99.0, max(75.0, ($sensorNormalized * 0.5) + ($visionScore > 0 ? $visionScore * 0.5 : 45.0))), 2);

        $summary = sprintf(
            'Sıcaklık: %.1f°C, Nem: %%%.1f, Duman: %.1f ppm, Gaz: %.1f ppm. AI Görsel Güven: %%%.1f (%s)',
            $temperature,
            $humidity,
            $smokePpm,
            $coGasPpm,
            $visionScore,
            $flameDetected ? 'Alev Tespit Edildi' : ($smokeDetected ? 'Duman Tespit Edildi' : 'Normal Görsel')
        );

        return [
            'risk_score' => $compoundRisk,
            'severity' => $severity,
            'should_trigger_alarm' => $shouldTriggerAlarm,
            'ai_confidence' => $aiConfidence,
            'breakdown' => [
                'temperature_score' => round($tempScore, 2),
                'humidity_score' => round($humidityScore, 2),
                'smoke_score' => round($smokeScore, 2),
                'gas_score' => round($gasScore, 2),
                'vision_score' => round($visionScore, 2),
                'flame_detected' => $flameDetected,
                'smoke_detected' => $smokeDetected,
            ],
            'summary' => $summary,
        ];
    }
}
