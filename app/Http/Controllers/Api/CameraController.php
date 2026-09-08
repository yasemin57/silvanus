<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Camera::query();

        if ($request->filled('zone_name')) {
            $query->where('zone_name', $request->string('zone_name'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $camera = Camera::query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $camera,
        ]);
    }
}
