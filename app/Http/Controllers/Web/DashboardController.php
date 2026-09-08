<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\FireIncident;
use App\Models\FireStation;
use App\Models\Sensor;
use App\Services\Health\SystemHealthMonitorService;
use App\Services\Incidents\IncidentLifecycleService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SystemHealthMonitorService $healthService,
        private readonly IncidentLifecycleService $lifecycleService
    ) {}

    public function index(): View
    {
        $activeIncidents = FireIncident::query()
            ->active()
            ->with(['fireStation', 'primarySensor.latestReading', 'primaryCamera', 'assignments.firefighter.user', 'timelines'])
            ->latest('detected_at')
            ->get();

        $latestCritical = $activeIncidents->first();

        $sensors = Sensor::query()->with('latestReading')->get();
        $cameras = Camera::query()->get();
        $stations = FireStation::query()->with(['firefighters.user'])->where('is_active', true)->get();
        $health = $this->healthService->getHealthSummary();

        return view('dashboard.index', compact(
            'activeIncidents',
            'latestCritical',
            'sensors',
            'cameras',
            'stations',
            'health'
        ));
    }

    public function incidents(): View
    {
        $incidents = FireIncident::query()
            ->with(['fireStation', 'primarySensor', 'primaryCamera', 'timelines'])
            ->latest('detected_at')
            ->paginate(15);

        return view('incidents.index', compact('incidents'));
    }

    public function showIncident(int $id): View
    {
        $incident = FireIncident::query()
            ->with([
                'fireStation.firefighters.user',
                'primarySensor.readings' => fn ($q) => $q->latest('recorded_at')->limit(20),
                'primaryCamera',
                'assignments.firefighter.user',
                'timelines.user',
            ])
            ->findOrFail($id);

        $kpis = $this->lifecycleService->computeKpis($incident);

        return view('incidents.detail', compact('incident', 'kpis'));
    }

    public function simulator(): View
    {
        $sensors = Sensor::query()->get();
        $cameras = Camera::query()->get();
        $stations = FireStation::query()->where('is_active', true)->get();

        return view('simulator.index', compact('sensors', 'cameras', 'stations'));
    }
}
