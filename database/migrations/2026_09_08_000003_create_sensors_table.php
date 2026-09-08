<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('sensor_code')->unique();
            $table->string('name');
            $table->string('zone_name')->default('Silvanus Bölgesi 01');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->string('model')->default('Silvanus-IoT-V3');
            $table->integer('battery_level')->default(100);
            $table->string('status')->default('ACTIVE'); // ACTIVE, WARNING, OFFLINE
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['zone_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
