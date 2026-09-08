<?php

declare(strict_types=1);

namespace App\Services\Incidents;

use App\Enums\IncidentStatus;
use App\Models\FireIncident;
use App\Models\IncidentAssignment;
use App\Models\IncidentTimeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class IncidentLifecycleService
{
    /**
     * Allowed transition graph.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'DETECTED' => ['NOTIFIED', 'ACCEPTED'],
        'NOTIFIED' => ['ACCEPTED'],
        'ACCEPTED' => ['ON_THE_WAY'],
        'ON_THE_WAY' => ['ARRIVED'],
        'ARRIVED' => ['UNDER_CONTROL'],
        'UNDER_CONTROL' => ['RESOLVED'],
        'RESOLVED' => [],
    ];

    /**
     * Transition an incident to a new state and record timeline with duration.
     *
     * @throws ValidationException
     */
    public function transition(
        FireIncident $incident,
        IncidentStatus $targetStatus,
        ?User $user = null,
        ?string $note = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): FireIncident {
        $currentStatus = $incident->status->value;
        $targetValue = $targetStatus->value;

        // Verify transition validity
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        if (! in_array($targetValue, $allowed, true) && $currentStatus !== $targetValue) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Geçersiz durum geçişi: %s durumundan %s durumuna geçilemez.',
                    $incident->status->label(),
                    $targetStatus->label()
                ),
            ]);
        }

        $now = Carbon::now();

        // Calculate duration from previous timeline step
        $lastTimeline = $incident->timelines()->latest('recorded_at')->first();
        $durationSeconds = $lastTimeline
            ? (int) abs($now->diffInSeconds($lastTimeline->recorded_at))
            : (int) abs($now->diffInSeconds($incident->detected_at ?? $incident->created_at));

        // Update incident status
        $incident->status = $targetStatus;
        if ($targetStatus === IncidentStatus::Resolved) {
            $incident->resolved_at = $now;
        }
        $incident->save();

        // Create timeline entry
        IncidentTimeline::query()->create([
            'fire_incident_id' => $incident->id,
            'user_id' => $user?->id,
            'status_from' => $currentStatus,
            'status_to' => $targetValue,
            'action_title' => $this->getActionTitle($targetStatus),
            'note' => $note ?? $this->getDefaultNote($targetStatus, $user),
            'latitude' => $latitude ?? $incident->latitude,
            'longitude' => $longitude ?? $incident->longitude,
            'duration_from_previous_seconds' => $durationSeconds,
            'recorded_at' => $now,
        ]);

        // Synchronize firefighter assignment if available
        if ($user && $user->firefighter) {
            $assignment = IncidentAssignment::query()
                ->where('fire_incident_id', $incident->id)
                ->where('firefighter_id', $user->firefighter->id)
                ->first();

            if ($assignment) {
                match ($targetStatus) {
                    IncidentStatus::Accepted => $assignment->update(['status' => 'ACCEPTED', 'accepted_at' => $now]),
                    IncidentStatus::OnTheWay => $assignment->update(['status' => 'ON_THE_WAY', 'departed_at' => $now]),
                    IncidentStatus::Arrived => $assignment->update(['status' => 'ARRIVED', 'arrived_at' => $now]),
                    IncidentStatus::Resolved => $assignment->update(['status' => 'COMPLETED', 'completed_at' => $now]),
                    default => null,
                };
            }
        }

        return $incident->fresh(['timelines.user', 'assignments.firefighter', 'fireStation', 'primarySensor', 'primaryCamera']);
    }

    private function getActionTitle(IncidentStatus $status): string
    {
        return match ($status) {
            IncidentStatus::Detected => 'Yangın Tespit Edildi',
            IncidentStatus::Notified => 'İtfaiye Personeline Bildirim Gönderildi',
            IncidentStatus::Accepted => 'Olay Kabul Edildi',
            IncidentStatus::OnTheWay => 'İtfaiye Ekibi Yola Çıktı',
            IncidentStatus::Arrived => 'Olay Bölgesine Ulaşıldı',
            IncidentStatus::UnderControl => 'Yangın Kontrol Altına Alındı',
            IncidentStatus::Resolved => 'Yangın Tamamen Söndürüldü ve Kapatıldı',
        };
    }

    private function getDefaultNote(IncidentStatus $status, ?User $user): string
    {
        $actor = $user ? $user->name : 'Sistem';

        return match ($status) {
            IncidentStatus::Detected => 'Yerel sunucu AI analizi ile yangın otomatik olarak doğrulandı.',
            IncidentStatus::Notified => 'En yakın itfaiye merkezindeki personele push notification fırlatıldı.',
            IncidentStatus::Accepted => sprintf('%s tarafından olay kabul edildi ve hazırlık tamamlandı.', $actor),
            IncidentStatus::OnTheWay => sprintf('%s ve ekibi yangın koordinatına doğru intikale geçti.', $actor),
            IncidentStatus::Arrived => sprintf('%s olay yerine vardı. Söndürme müdahalesi başlatıldı.', $actor),
            IncidentStatus::UnderControl => 'Yangının yayılması engellendi, soğutma çalışmaları sürüyor.',
            IncidentStatus::Resolved => 'Soğutma tamamlandı, saha güvenli. Olay arşive aktarıldı.',
        };
    }

    /**
     * Compute comprehensive performance KPIs for an incident.
     *
     * @return array<string, mixed>
     */
    public function computeKpis(FireIncident $incident): array
    {
        $timelines = $incident->timelines()->orderBy('recorded_at')->get();

        $findTime = function (string $status) use ($timelines): ?Carbon {
            $t = $timelines->firstWhere('status_to', $status);

            return $t ? Carbon::parse($t->recorded_at) : null;
        };

        $tDetected = $incident->detected_at ?? Carbon::parse($incident->created_at);
        $tNotified = $findTime('NOTIFIED');
        $tAccepted = $findTime('ACCEPTED');
        $tOnTheWay = $findTime('ON_THE_WAY');
        $tArrived = $findTime('ARRIVED');
        $tUnderControl = $findTime('UNDER_CONTROL');
        $tResolved = $findTime('RESOLVED');

        return [
            'detection_to_notification_sec' => $tNotified ? $tDetected->diffInSeconds($tNotified) : null,
            'notification_to_acceptance_sec' => ($tNotified && $tAccepted) ? $tNotified->diffInSeconds($tAccepted) : null,
            'acceptance_to_departure_sec' => ($tAccepted && $tOnTheWay) ? $tAccepted->diffInSeconds($tOnTheWay) : null,
            'departure_to_arrival_sec' => ($tOnTheWay && $tArrived) ? $tOnTheWay->diffInSeconds($tArrived) : null,
            'arrival_to_control_sec' => ($tArrived && $tUnderControl) ? $tArrived->diffInSeconds($tUnderControl) : null,
            'total_duration_sec' => $tResolved ? $tDetected->diffInSeconds($tResolved) : null,
        ];
    }
}
