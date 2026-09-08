<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\FireIncident;
use App\Models\User;
use App\Services\Incidents\IncidentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IncidentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private IncidentLifecycleService $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lifecycle = new IncidentLifecycleService;
    }

    public function test_full_incident_lifecycle_flow(): void
    {
        $user = User::factory()->create();

        $incident = FireIncident::query()->create([
            'incident_code' => 'INC-TEST-01',
            'title' => 'Test Yangın Olayı',
            'latitude' => 39.7190,
            'longitude' => 26.8710,
            'risk_score' => 95.0,
            'severity' => IncidentSeverity::Critical,
            'status' => IncidentStatus::Detected,
            'detected_at' => now(),
        ]);

        // 1. DETECTED -> NOTIFIED
        $incident = $this->lifecycle->transition($incident, IncidentStatus::Notified, $user);
        $this->assertEquals(IncidentStatus::Notified, $incident->status);

        // 2. NOTIFIED -> ACCEPTED
        $incident = $this->lifecycle->transition($incident, IncidentStatus::Accepted, $user);
        $this->assertEquals(IncidentStatus::Accepted, $incident->status);

        // 3. ACCEPTED -> ON_THE_WAY
        $incident = $this->lifecycle->transition($incident, IncidentStatus::OnTheWay, $user);
        $this->assertEquals(IncidentStatus::OnTheWay, $incident->status);

        // 4. ON_THE_WAY -> ARRIVED
        $incident = $this->lifecycle->transition($incident, IncidentStatus::Arrived, $user);
        $this->assertEquals(IncidentStatus::Arrived, $incident->status);

        // 5. ARRIVED -> UNDER_CONTROL
        $incident = $this->lifecycle->transition($incident, IncidentStatus::UnderControl, $user);
        $this->assertEquals(IncidentStatus::UnderControl, $incident->status);

        // 6. UNDER_CONTROL -> RESOLVED
        $incident = $this->lifecycle->transition($incident, IncidentStatus::Resolved, $user);
        $this->assertEquals(IncidentStatus::Resolved, $incident->status);
        $this->assertNotNull($incident->resolved_at);

        $this->assertCount(6, $incident->timelines);
    }

    public function test_invalid_transition_throws_validation_exception(): void
    {
        $incident = FireIncident::query()->create([
            'incident_code' => 'INC-TEST-02',
            'title' => 'Test Yangın Olayı',
            'latitude' => 39.7190,
            'longitude' => 26.8710,
            'risk_score' => 90.0,
            'severity' => IncidentSeverity::Critical,
            'status' => IncidentStatus::Detected,
            'detected_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        // Skipping straight from DETECTED to RESOLVED should fail
        $this->lifecycle->transition($incident, IncidentStatus::Resolved);
    }
}
