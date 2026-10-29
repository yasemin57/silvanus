<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\FireIncident;
use App\Models\FireStation;
use App\Models\SensorData; // Sensor yerine SensorData modelini ekledik
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
            ->with(['fireStation', 'primaryCamera', 'assignments.firefighter.user', 'timelines'])
            ->latest('detected_at')
            ->get();

        $latestCritical = $activeIncidents->first();

        // Sensor yerine doğrudan ESP32 verilerini sakladığınız SensorData modelinden verileri çekiyoruz
        $sensors = SensorData::query()->latest()->get();
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
            ->with(['fireStation', 'primaryCamera', 'timelines'])
            ->latest('detected_at')
            ->paginate(15);

        return view('incidents.index', compact('incidents'));
    }

    public function showIncident(int $id): View
    {
        $incident = FireIncident::query()
            ->with([
                'fireStation.firefighters.user',
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
        $sensors = SensorData::query()->latest()->take(10)->get();
        $cameras = Camera::query()->get();
        $stations = FireStation::query()->where('is_active', true)->get();

        return view('simulator.index', compact('sensors', 'cameras', 'stations'));
    }
}