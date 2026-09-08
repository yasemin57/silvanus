<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fire_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_code')->unique(); // e.g. INC-001245
            $table->string('title');
            $table->string('zone_name')->default('Silvanus Bölgesi 01');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->decimal('risk_score', 5, 2); // 0.00 - 100.00
            $table->string('severity')->default('CRITICAL'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->string('status')->default('DETECTED'); // DETECTED, NOTIFIED, ACCEPTED, ON_THE_WAY, ARRIVED, UNDER_CONTROL, RESOLVED
            $table->timestamp('detected_at')->useCurrent();
            $table->foreignId('primary_sensor_id')->nullable()->constrained('sensors')->nullOnDelete();
            $table->foreignId('primary_camera_id')->nullable()->constrained('cameras')->nullOnDelete();
            $table->foreignId('fire_station_id')->nullable()->constrained('fire_stations')->nullOnDelete();
            $table->decimal('ai_confidence', 5, 2)->default(95.00);
            $table->json('ai_analysis_summary')->nullable(); // contains sensor metrics and vision detection tags
            $table->json('weather_snapshot')->nullable(); // temperature, wind, humidity at detection time
            $table->decimal('estimated_area_m2', 10, 2)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['status', 'severity']);
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fire_incidents');
    }
};
