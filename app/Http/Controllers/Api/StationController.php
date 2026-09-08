<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireStation;
use Illuminate\Http\JsonResponse;

class StationController extends Controller
{
    public function index(): JsonResponse
    {
        $stations = FireStation::query()
            ->with(['firefighters.user'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stations,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $station = FireStation::query()
            ->with(['firefighters.user', 'fireIncidents' => fn ($q) => $q->latest('detected_at')->limit(5)])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $station,
        ]);
    }
}
