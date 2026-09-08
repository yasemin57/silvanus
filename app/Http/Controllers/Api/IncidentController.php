<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\IncidentStatus;
use App\Http\Controllers\Controller;
use App\Models\FireIncident;
use App\Services\Incidents\IncidentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentLifecycleService $lifecycleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = FireIncident::query()
            ->with([
                'fireStation',
                'primarySensor.latestReading',
                'primaryCamera',
                'assignments.firefighter.user',
            ])
            ->latest('detected_at');

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        if ($request->filled('zone_name')) {
            $query->where('zone_name', $request->string('zone_name'));
        }

        $incidents = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $incidents->items(),
            'meta' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'total' => $incidents->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $incident = FireIncident::query()
            ->with([
                'fireStation.firefighters.user',
                'primarySensor.readings' => fn ($q) => $q->latest('recorded_at')->limit(10),
                'primaryCamera',
                'assignments.firefighter.user',
                'timelines.user',
            ])
            ->findOrFail($id);

        $kpis = $this->lifecycleService->computeKpis($incident);

        return response()->json([
            'success' => true,
            'data' => $incident,
            'kpis' => $kpis,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $query = FireIncident::query()
            ->with(['fireStation', 'primarySensor', 'primaryCamera', 'timelines'])
            ->where('status', IncidentStatus::Resolved)
            ->latest('detected_at');

        if ($request->filled('zone_name')) {
            $query->where('zone_name', $request->string('zone_name'));
        }

        $incidents = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $incidents->items(),
            'meta' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'total' => $incidents->total(),
            ],
        ]);
    }

    public function transition(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $incident = FireIncident::query()->findOrFail($id);
        $targetStatus = IncidentStatus::from($validated['status']);

        $updatedIncident = $this->lifecycleService->transition(
            incident: $incident,
            targetStatus: $targetStatus,
            user: $request->user(),
            note: $validated['note'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        );

        return response()->json([
            'success' => true,
            'message' => sprintf('Olay durumu "%s" olarak güncellendi.', $targetStatus->label()),
            'data' => $updatedIncident,
        ]);
    }
}
