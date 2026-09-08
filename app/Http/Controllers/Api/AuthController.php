<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'fcm_device_token' => ['nullable', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Geçersiz e-posta adresi veya şifre.'],
            ]);
        }

        // Update firefighter FCM token if provided
        if ($user->firefighter && ! empty($validated['fcm_device_token'])) {
            $user->firefighter->update([
                'fcm_device_token' => $validated['fcm_device_token'],
            ]);
        }

        // Generate Sanctum token
        $token = $user->createToken('silvanus-mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'avatar_url' => $user->avatar_url,
                'firefighter' => $user->firefighter ? [
                    'id' => $user->firefighter->id,
                    'badge_number' => $user->firefighter->badge_number,
                    'rank' => $user->firefighter->rank,
                    'status' => $user->firefighter->status,
                    'station' => $user->firefighter->fireStation ? [
                        'id' => $user->firefighter->fireStation->id,
                        'name' => $user->firefighter->fireStation->name,
                        'code' => $user->firefighter->fireStation->code,
                        'latitude' => $user->firefighter->fireStation->latitude,
                        'longitude' => $user->firefighter->fireStation->longitude,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['firefighter.fireStation']);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'avatar_url' => $user->avatar_url,
                'firefighter' => $user->firefighter ? [
                    'id' => $user->firefighter->id,
                    'badge_number' => $user->firefighter->badge_number,
                    'rank' => $user->firefighter->rank,
                    'status' => $user->firefighter->status,
                    'station' => $user->firefighter->fireStation ? [
                        'id' => $user->firefighter->fireStation->id,
                        'name' => $user->firefighter->fireStation->name,
                        'code' => $user->firefighter->fireStation->code,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_device_token' => ['required', 'string'],
        ]);

        $user = $request->user();
        if ($user->firefighter) {
            $user->firefighter->update([
                'fcm_device_token' => $validated['fcm_device_token'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM cihaz tokeni başarıyla güncellendi.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Oturum başarıyla kapatıldı.',
        ]);
    }
}
