<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fire_incident_id')->constrained('fire_incidents')->cascadeOnDelete();
            $table->foreignId('firefighter_id')->constrained('firefighters')->cascadeOnDelete();
            $table->foreignId('fire_station_id')->constrained('fire_stations')->cascadeOnDelete();
            $table->string('status')->default('ASSIGNED'); // ASSIGNED, ACCEPTED, REJECTED, COMPLETED
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fire_incident_id', 'firefighter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_assignments');
    }
};
