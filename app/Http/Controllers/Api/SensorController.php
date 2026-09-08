<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Sensor::query()->with('latestReading');

        if ($request->filled('zone_name')) {
            $query->where('zone_name', $request->string('zone_name'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $sensors = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $sensors->items(),
            'meta' => [
                'current_page' => $sensors->currentPage(),
                'last_page' => $sensors->lastPage(),
                'total' => $sensors->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $sensor = Sensor::query()
            ->with(['readings' => fn ($q) => $q->latest('recorded_at')->limit(30)])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sensor,
        ]);
    }
}
