<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firefighters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fire_station_id')->constrained('fire_stations')->cascadeOnDelete();
            $table->string('badge_number')->unique();
            $table->string('rank')->default('Er');
            $table->string('phone')->nullable();
            $table->string('status')->default('AVAILABLE'); // AVAILABLE, DISPATCHED, OFF_DUTY
            $table->text('fcm_device_token')->nullable();
            $table->decimal('last_known_latitude', 10, 7)->nullable();
            $table->decimal('last_known_longitude', 10, 7)->nullable();
            $table->timestamp('last_location_updated_at')->nullable();
            $table->timestamps();

            $table->index(['fire_station_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firefighters');
    }
};
