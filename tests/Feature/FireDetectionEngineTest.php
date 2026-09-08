<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\IncidentSeverity;
use App\Services\AI\FireRiskAnalysisService;
use Tests\TestCase;

class FireDetectionEngineTest extends TestCase
{
    private FireRiskAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FireRiskAnalysisService;
    }

    public function test_normal_forest_conditions_yield_low_risk(): void
    {
        $result = $this->service->evaluate(
            temperature: 24.5,
            humidity: 62.0,
            smokePpm: 8.0,
            coGasPpm: 4.0,
            visionConfidence: 0.0,
            flameDetected: false,
            smokeDetected: false
        );

        $this->assertLessThan(40.0, $result['risk_score']);
        $this->assertEquals(IncidentSeverity::Low, $result['severity']);
        $this->assertFalse($result['should_trigger_alarm']);
    }

    public function test_critical_wildfire_conditions_trigger_alarm_with_critical_severity(): void
    {
        $result = $this->service->evaluate(
            temperature: 72.0,
            humidity: 13.0,
            smokePpm: 92.0,
            coGasPpm: 45.0,
            visionConfidence: 96.0,
            flameDetected: true,
            smokeDetected: true
        );

        $this->assertGreaterThanOrEqual(85.0, $result['risk_score']);
        $this->assertEquals(IncidentSeverity::Critical, $result['severity']);
        $this->assertTrue($result['should_trigger_alarm']);
        $this->assertGreaterThan(90.0, $result['ai_confidence']);
    }
}
