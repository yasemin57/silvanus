<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Models\Camera;
use App\Models\Firefighter;
use App\Models\FireIncident;
use App\Models\FireStation;
use App\Models\IncidentAssignment;
use App\Models\IncidentTimeline;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\SystemHealthLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::query()->create([
            'name' => 'Kemal Aksoy (Merkez Yönetici)',
            'email' => 'admin@silvanus.gov.tr',
            'password' => Hash::make('silvanus123'),
            'phone' => '+90 532 100 0001',
            'role' => UserRole::Admin,
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
        ]);

        $operator = User::query()->create([
            'name' => 'Elif Yılmaz (Nöbetçi Operatör)',
            'email' => 'operator@silvanus.gov.tr',
            'password' => Hash::make('silvanus123'),
            'phone' => '+90 532 100 0002',
            'role' => UserRole::Operator,
            'avatar_url' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150',
        ]);

        $chiefUser = User::query()->create([
            'name' => 'Murat Demir (İtfaiye Amiri)',
            'email' => 'chief@silvanus.gov.tr',
            'password' => Hash::make('silvanus123'),
            'phone' => '+90 532 100 0003',
            'role' => UserRole::FireChief,
            'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
        ]);

        $ffUser1 = User::query()->create([
            'name' => 'Ahmet Kaya (İtfaiye Eri)',
            'email' => 'ahmet@silvanus.gov.tr',
            'password' => Hash::make('silvanus123'),
            'phone' => '+90 532 100 0004',
            'role' => UserRole::Firefighter,
            'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150',
        ]);

        $ffUser2 = User::query()->create([
            'name' => 'Burak Çelik (Arazöz Şoförü)',
            'email' => 'burak@silvanus.gov.tr',
            'password' => Hash::make('silvanus123'),
            'phone' => '+90 532 100 0005',
            'role' => UserRole::Firefighter,
            'avatar_url' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150',
        ]);

        $ffUser3 = User::query()->create([
            'name' => 'Serkan Öztürk (İtfaiye Çavuşu)',
            'email' => 'serkan@silvanus.gov.tr',
            'password' => Hash::make('silvanus123'),
            'phone' => '+90 532 100 0006',
            'role' => UserRole::Firefighter,
            'avatar_url' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150',
        ]);

        // 2. Fire Stations (Centered around Kazdağları & Aegean Forest Region)
        $station1 = FireStation::query()->create([
            'code' => 'STN-KAZ-01',
            'name' => 'Kazdağları Yangın İlk Müdahale Merkezi (Merkez #1)',
            'latitude' => 39.7124000,
            'longitude' => 26.8621000,
            'altitude' => 840.0,
            'address' => 'Kazdağı Milli Parkı Girişi No:12, Edremit / Balıkesir',
            'phone' => '+90 266 373 1111',
            'coverage_radius_km' => 28.0,
            'active_units_count' => 6,
            'is_active' => true,
        ]);

        $station2 = FireStation::query()->create([
            'code' => 'STN-EDR-02',
            'name' => 'Edremit Orman Yangın İstasyonu (Merkez #2)',
            'latitude' => 39.5938000,
            'longitude' => 27.0244000,
            'altitude' => 65.0,
            'address' => 'Cennetayağı Mah. Orman Cad. No:4, Edremit',
            'phone' => '+90 266 374 2222',
            'coverage_radius_km' => 35.0,
            'active_units_count' => 8,
            'is_active' => true,
        ]);

        $station3 = FireStation::query()->create([
            'code' => 'STN-AYV-03',
            'name' => 'Ayvalık Orman İşletme Şefliği (Merkez #3)',
            'latitude' => 39.3195000,
            'longitude' => 26.6963000,
            'altitude' => 30.0,
            'address' => 'Ali Çetinkaya Mah. Sahil Cad., Ayvalık',
            'phone' => '+90 266 312 3333',
            'coverage_radius_km' => 30.0,
            'active_units_count' => 5,
            'is_active' => true,
        ]);

        $station4 = FireStation::query()->create([
            'code' => 'STN-CAN-04',
            'name' => 'Çanakkale Bölge Orman Yangın Üssü (Merkez #4)',
            'latitude' => 40.1553000,
            'longitude' => 26.4142000,
            'altitude' => 110.0,
            'address' => 'Esenler Mah. Çanakkale',
            'phone' => '+90 286 217 4444',
            'coverage_radius_km' => 50.0,
            'active_units_count' => 12,
            'is_active' => true,
        ]);

        // 3. Firefighters linked to Stations
        $ff1 = Firefighter::query()->create([
            'user_id' => $chiefUser->id,
            'fire_station_id' => $station1->id,
            'badge_number' => 'AMIR-1001',
            'rank' => 'İtfaiye Amiri',
            'phone' => $chiefUser->phone,
            'status' => 'AVAILABLE',
            'fcm_device_token' => 'fcm-token-chief-sample-12345',
            'last_known_latitude' => 39.7124000,
            'last_known_longitude' => 26.8621000,
        ]);

        $ff2 = Firefighter::query()->create([
            'user_id' => $ffUser1->id,
            'fire_station_id' => $station1->id,
            'badge_number' => 'ER-2042',
            'rank' => 'Kıdemli İtfaiye Eri',
            'phone' => $ffUser1->phone,
            'status' => 'AVAILABLE',
            'fcm_device_token' => 'fcm-token-ahmet-sample-67890',
            'last_known_latitude' => 39.7125000,
            'last_known_longitude' => 26.8623000,
        ]);

        $ff3 = Firefighter::query()->create([
            'user_id' => $ffUser2->id,
            'fire_station_id' => $station2->id,
            'badge_number' => 'SOF-3011',
            'rank' => 'Arazöz Şoförü',
            'phone' => $ffUser2->phone,
            'status' => 'AVAILABLE',
            'fcm_device_token' => 'fcm-token-burak-sample-11223',
            'last_known_latitude' => 39.5938000,
            'last_known_longitude' => 27.0244000,
        ]);

        // 4. Forest Sensors Grid (Kazdağları Zone 01, 02, 03)
        $sensorCoords = [
            ['code' => 'SNS-101', 'name' => 'Sensör #101 (Kazdağı Sarıkız Zirve)', 'lat' => 39.7285, 'lng' => 26.8420, 'alt' => 1720, 'zone' => 'Silvanus Bölgesi 03', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-102', 'name' => 'Sensör #102 (Kazdağı Zeytinli Kanyonu)', 'lat' => 39.7190, 'lng' => 26.8710, 'alt' => 650, 'zone' => 'Silvanus Bölgesi 03', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-103', 'name' => 'Sensör #103 (Hasanboğuldu Göleti Çıkışı)', 'lat' => 39.6840, 'lng' => 26.8920, 'alt' => 340, 'zone' => 'Silvanus Bölgesi 02', 'status' => DeviceStatus::Offline],
            ['code' => 'SNS-104', 'name' => 'Sensör #104 (Şahindere Kanyonu Batı)', 'lat' => 39.6250, 'lng' => 26.8150, 'alt' => 780, 'zone' => 'Silvanus Bölgesi 02', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-105', 'name' => 'Sensör #105 (Güre Kaplıcaları Orman Sınırı)', 'lat' => 39.6010, 'lng' => 26.8850, 'alt' => 120, 'zone' => 'Silvanus Bölgesi 01', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-106', 'name' => 'Sensör #106 (Altınoluk Yamaç Ormanı)', 'lat' => 39.5820, 'lng' => 26.7450, 'alt' => 450, 'zone' => 'Silvanus Bölgesi 01', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-107', 'name' => 'Sensör #107 (Narlı Köyü Kızılçam Ormanı)', 'lat' => 39.5630, 'lng' => 26.6820, 'alt' => 210, 'zone' => 'Silvanus Bölgesi 01', 'status' => DeviceStatus::Warning],
            ['code' => 'SNS-108', 'name' => 'Sensör #108 (Küçükkuyu Sırtları)', 'lat' => 39.5540, 'lng' => 26.6120, 'alt' => 390, 'zone' => 'Silvanus Bölgesi 01', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-109', 'name' => 'Sensör #109 (Havran Çam Ormanlığı)', 'lat' => 39.6120, 'lng' => 27.1200, 'alt' => 280, 'zone' => 'Silvanus Bölgesi 04', 'status' => DeviceStatus::Active],
            ['code' => 'SNS-110', 'name' => 'Sensör #110 (Burhaniye Taylıeli Tepe)', 'lat' => 39.5050, 'lng' => 26.9850, 'alt' => 310, 'zone' => 'Silvanus Bölgesi 04', 'status' => DeviceStatus::Active],
        ];

        $createdSensors = [];
        foreach ($sensorCoords as $sc) {
            $sensor = Sensor::query()->create([
                'sensor_code' => $sc['code'],
                'name' => $sc['name'],
                'zone_name' => $sc['zone'],
                'latitude' => $sc['lat'],
                'longitude' => $sc['lng'],
                'altitude' => $sc['alt'],
                'battery_level' => $sc['status'] === DeviceStatus::Offline ? 12 : random_int(85, 99),
                'status' => $sc['status'],
                'installed_at' => Carbon::now()->subMonths(6),
                'last_ping_at' => $sc['status'] === DeviceStatus::Offline ? Carbon::now()->subMinutes(45) : Carbon::now()->subSeconds(random_int(3, 15)),
            ]);

            $createdSensors[$sc['code']] = $sensor;

            // Generate baseline readings
            $isFireZone = ($sc['code'] === 'SNS-102');
            for ($i = 10; $i >= 0; $i--) {
                $temp = $isFireZone ? 65.0 + ($i === 0 ? 7.0 : random_int(0, 5)) : 24.0 + random_int(0, 4);
                $hum = $isFireZone ? 13.0 + random_int(0, 2) : 58.0 + random_int(-5, 5);
                $smoke = $isFireZone ? 92.0 + ($i === 0 ? 3.0 : random_int(0, 5)) : 8.0 + random_int(0, 3);
                $co = $isFireZone ? 45.0 + random_int(0, 6) : 4.0 + random_int(0, 2);

                SensorReading::query()->create([
                    'sensor_id' => $sensor->id,
                    'temperature' => $temp,
                    'humidity' => $hum,
                    'smoke_ppm' => $smoke,
                    'co_gas_ppm' => $co,
                    'air_quality_index' => $isFireZone ? 420 : 35,
                    'recorded_at' => Carbon::now()->subMinutes($i * 2),
                ]);
            }
        }

        // 5. Cameras
        $camerasData = [
            ['code' => 'CAM-021', 'name' => 'Kamera #21 (Sarıkız Yangın Gözetleme Kulesi)', 'lat' => 39.7290, 'lng' => 26.8430, 'zone' => 'Silvanus Bölgesi 03', 'status' => DeviceStatus::Active, 'stream' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'snapshot' => 'https://images.unsplash.com/photo-1516214104703-d870798883c5?w=800'],
            ['code' => 'CAM-022', 'name' => 'Kamera #22 (Zeytinli Vadi Termal PTZ)', 'lat' => 39.7180, 'lng' => 26.8720, 'zone' => 'Silvanus Bölgesi 03', 'status' => DeviceStatus::Active, 'stream' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 'snapshot' => 'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?w=800'],
            ['code' => 'CAM-023', 'name' => 'Kamera #23 (Hasanboğuldu Orman İçi Kamera)', 'lat' => 39.6850, 'lng' => 26.8910, 'zone' => 'Silvanus Bölgesi 02', 'status' => DeviceStatus::Offline, 'stream' => '', 'snapshot' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=800'],
            ['code' => 'CAM-024', 'name' => 'Kamera #24 (Şahindere Boğazı Optik)', 'lat' => 39.6260, 'lng' => 26.8140, 'zone' => 'Silvanus Bölgesi 02', 'status' => DeviceStatus::Active, 'stream' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 'snapshot' => 'https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=800'],
            ['code' => 'CAM-025', 'name' => 'Kamera #25 (Altınoluk Panoramik Gözetleme)', 'lat' => 39.5830, 'lng' => 26.7440, 'zone' => 'Silvanus Bölgesi 01', 'status' => DeviceStatus::Active, 'stream' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 'snapshot' => 'https://images.unsplash.com/photo-1502082553048-f009c37129b9?w=800'],
        ];

        $createdCameras = [];
        foreach ($camerasData as $cd) {
            $cam = Camera::query()->create([
                'camera_code' => $cd['code'],
                'name' => $cd['name'],
                'zone_name' => $cd['zone'],
                'latitude' => $cd['lat'],
                'longitude' => $cd['lng'],
                'altitude' => 950.0,
                'stream_url' => $cd['stream'],
                'snapshot_url' => $cd['snapshot'],
                'fov_degrees' => 140,
                'orientation_degrees' => 45,
                'resolution' => '4K Ultra HD Dual Optical+Thermal',
                'has_thermal' => true,
                'status' => $cd['status'],
                'last_ping_at' => $cd['status'] === DeviceStatus::Offline ? Carbon::now()->subMinutes(35) : Carbon::now()->subSeconds(5),
            ]);

            $createdCameras[$cd['code']] = $cam;
        }

        // 6. Active Wildfire Incident (Requirement Example: Incident #001245, Risk 96%, Critical)
        $fireIncident = FireIncident::query()->create([
            'incident_code' => 'INC-001245',
            'title' => 'Silvanus Bölgesi 03 - Zeytinli Kanyonu Yangın Alarmı',
            'zone_name' => 'Silvanus Bölgesi 03',
            'latitude' => 39.7190000,
            'longitude' => 26.8710000,
            'altitude' => 650.0,
            'risk_score' => 96.00,
            'severity' => IncidentSeverity::Critical,
            'status' => IncidentStatus::Notified,
            'detected_at' => Carbon::now()->subMinutes(8),
            'primary_sensor_id' => $createdSensors['SNS-102']->id,
            'primary_camera_id' => $createdCameras['CAM-022']->id,
            'fire_station_id' => $station1->id,
            'ai_confidence' => 97.5,
            'ai_analysis_summary' => [
                'summary' => 'Sıcaklık: 72.0°C, Nem: %13, Duman: 92.0 ppm, Gaz: Yüksek. AI Görsel: Alev ve Yoğun Duman Tespiti (%98)',
                'breakdown' => [
                    'temperature_score' => 30.0,
                    'humidity_score' => 20.0,
                    'smoke_score' => 25.0,
                    'gas_score' => 15.0,
                    'vision_score' => 98.0,
                    'flame_detected' => true,
                    'smoke_detected' => true,
                ],
                'detected_by' => [
                    'sensor' => 'SNS-102',
                    'camera' => 'CAM-022',
                ],
                'dispatch' => [
                    'station_name' => $station1->name,
                    'distance_km' => 2.4,
                    'estimated_minutes' => 6,
                ],
            ],
            'weather_snapshot' => [
                'temperature' => 72.0,
                'humidity' => 13.0,
                'smoke_ppm' => 92.0,
                'wind_speed_kmh' => 34.0,
                'wind_direction' => 'Kuzey-Kuzeydoğu (NNE)',
            ],
            'estimated_area_m2' => 850.0,
        ]);

        // Timelines for Incident #001245
        IncidentTimeline::query()->create([
            'fire_incident_id' => $fireIncident->id,
            'status_from' => null,
            'status_to' => IncidentStatus::Detected->value,
            'action_title' => 'Yangın Yerel Sunucu AI Tarafından Tespit Edildi',
            'note' => 'Sensör #102 sıcaklık (72°C) ve duman (92 ppm) anomalisi, Kamera #22 alev tespiti ile birleştirildi. Risk skoru %96 (Kritik).',
            'latitude' => 39.7190000,
            'longitude' => 26.8710000,
            'duration_from_previous_seconds' => 0,
            'recorded_at' => Carbon::now()->subMinutes(8),
        ]);

        IncidentTimeline::query()->create([
            'fire_incident_id' => $fireIncident->id,
            'status_from' => IncidentStatus::Detected->value,
            'status_to' => IncidentStatus::Notified->value,
            'action_title' => 'İtfaiye Merkezine Push Notification Gönderildi',
            'note' => 'En yakın istasyon Kazdağları İlk Müdahale Merkezi (2.4 km) belirlendi ve nöbetçi itfaiye ekiplerine bildirim fırlatıldı.',
            'latitude' => 39.7190000,
            'longitude' => 26.8710000,
            'duration_from_previous_seconds' => 3,
            'recorded_at' => Carbon::now()->subMinutes(8)->addSeconds(3),
        ]);

        // Assignments
        IncidentAssignment::query()->create([
            'fire_incident_id' => $fireIncident->id,
            'firefighter_id' => $ff1->id,
            'fire_station_id' => $station1->id,
            'status' => 'ASSIGNED',
            'assigned_at' => Carbon::now()->subMinutes(8)->addSeconds(3),
        ]);

        IncidentAssignment::query()->create([
            'fire_incident_id' => $fireIncident->id,
            'firefighter_id' => $ff2->id,
            'fire_station_id' => $station1->id,
            'status' => 'ASSIGNED',
            'assigned_at' => Carbon::now()->subMinutes(8)->addSeconds(3),
        ]);

        // 7. System Health Initial Log
        SystemHealthLog::query()->create([
            'component' => 'EDGE_SERVER',
            'status' => 'HEALTHY',
            'message' => 'Silvanus Yerel Edge Sunucusu aktif. Yapay zekâ çıkarım gecikmesi 42ms.',
            'metrics' => ['fps' => 30, 'cpu_load' => 28.4, 'ram_usage_mb' => 1420],
            'logged_at' => now(),
        ]);
    }
}
