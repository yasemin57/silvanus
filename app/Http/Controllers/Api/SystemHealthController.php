<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Health\SystemHealthMonitorService;
use Illuminate\Http\JsonResponse;

class SystemHealthController extends Controller
{
    public function __construct(
        private readonly SystemHealthMonitorService $healthService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->healthService->getHealthSummary(),
        ]);
    }
}
