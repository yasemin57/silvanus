<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensors')->cascadeOnDelete();
            $table->decimal('temperature', 5, 2); // in Celsius (e.g. 72.50)
            $table->decimal('humidity', 5, 2);    // in % (e.g. 13.20)
            $table->decimal('smoke_ppm', 8, 2);   // in PPM (e.g. 92.40)
            $table->decimal('co_gas_ppm', 8, 2);  // in PPM (e.g. 45.10)
            $table->integer('air_quality_index')->nullable();
            $table->decimal('flame_raw_value', 8, 2)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['sensor_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
