<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    // Web paneli (React/Dashboard) tüm sensör geçmişini ve sayaçları buradan çeker
    public function index()
    {
        $data = SensorData::latest()->get();
        $latest = $data->first();

        return response()->json([
            'status' => 'success',
            'total_sensors' => $data->count() > 0 ? 1 : 0, // Aktif sensör düğüm sayısı
            'latest' => $latest,
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'temperature' => 'nullable|numeric',
            'humidity'    => 'nullable|numeric',
            'gas_level'   => 'nullable|integer',
        ]);

        $data = SensorData::create([
            'temperature' => $request->temperature,
            'humidity'    => $request->humidity,
            'gas_level'   => $request->gas_level,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Sensör verisi kaydedildi.',
            'data'    => $data
        ], 201);
    }

    public function latest()
    {
        $latest = SensorData::latest()->first();

        if (!$latest) {
            return response()->json([
                'status' => 'success',
                'temperature' => 0,
                'humidity' => 0,
                'gas_level' => 0,
                'data' => [
                    'temperature' => 0,
                    'humidity' => 0,
                    'gas_level' => 0,
                    'created_at' => null
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            // Hem doğrudan kök dizinde hem 'data' içinde dönerek tüm frontend formatlarını destekliyoruz
            'temperature' => $latest->temperature,
            'humidity' => $latest->humidity,
            'gas_level' => $latest->gas_level,
            'data' => [
                'temperature' => $latest->temperature,
                'humidity' => $latest->humidity,
                'gas_level' => $latest->gas_level,
                'created_at' => $latest->created_at ? $latest->created_at->format('Y-m-d H:i:s') : null
            ]
        ]);
    }
}