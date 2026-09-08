<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Firefighter;
use App\Models\FireIncident;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirePushNotificationService
{
    /**
     * Dispatch critical wildfire push notification to assigned firefighters.
     *
     * @param  Collection<int, Firefighter>|array<int, Firefighter>  $firefighters
     * @return array{
     *     success: bool,
     *     recipient_count: int,
     *     payload: array<string, mixed>,
     *     message_id: string
     * }
     */
    public function sendIncidentAlert(FireIncident $incident, Collection|array $firefighters): array
    {
        $recipients = collect($firefighters);
        $tokens = $recipients
            ->pluck('fcm_device_token')
            ->filter()
            ->values()
            ->all();

        $payload = [
            'notification' => [
                'title' => sprintf('🚨 %s YANGIN UYARISI', $incident->severity->label()),
                'body' => sprintf(
                    '%s - Risk Skoru: %%%.0f. Konum otomatik belirlendi.',
                    $incident->zone_name,
                    $incident->risk_score
                ),
                'sound' => 'emergency_alarm',
                'android_channel_id' => 'silvanus_emergency_channel',
            ],
            'data' => [
                'type' => 'FIRE_INCIDENT_ALERT',
                'incident_id' => (string) $incident->id,
                'incident_code' => $incident->incident_code,
                'title' => $incident->title,
                'zone_name' => $incident->zone_name,
                'latitude' => (string) $incident->latitude,
                'longitude' => (string) $incident->longitude,
                'risk_score' => (string) $incident->risk_score,
                'severity' => $incident->severity->value,
                'status' => $incident->status->value,
                'deep_link' => sprintf('silvanus://incident/%d', $incident->id),
                'camera_stream_url' => $incident->primaryCamera?->stream_url ?? '',
                'station_name' => $incident->fireStation?->name ?? '',
                'detected_at' => $incident->detected_at?->toISOString() ?? now()->toISOString(),
            ],
        ];

        // FCM API v1 simulation or live dispatch
        $fcmServerKey = config('services.fcm.server_key');
        $messageId = 'fcm-msg-'.uniqid();

        if (! empty($fcmServerKey) && count($tokens) > 0) {
            try {
                Http::withHeaders([
                    'Authorization' => 'key='.$fcmServerKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => $tokens,
                    'notification' => $payload['notification'],
                    'data' => $payload['data'],
                    'priority' => 'high',
                ]);
            } catch (\Throwable $e) {
                Log::warning('FCM HTTP send exception: '.$e->getMessage());
            }
        }

        Log::info('SILVANUS PUSH NOTIFICATION DISPATCHED', [
            'incident_code' => $incident->incident_code,
            'recipients_count' => $recipients->count(),
            'tokens_count' => count($tokens),
            'payload' => $payload,
        ]);

        return [
            'success' => true,
            'recipient_count' => $recipients->count(),
            'payload' => $payload,
            'message_id' => $messageId,
        ];
    }
}
