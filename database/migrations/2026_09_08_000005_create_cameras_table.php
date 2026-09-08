<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('camera_code')->unique();
            $table->string('name');
            $table->string('zone_name')->default('Silvanus Bölgesi 01');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->string('stream_url')->nullable();
            $table->string('snapshot_url')->nullable();
            $table->integer('fov_degrees')->default(120);
            $table->integer('orientation_degrees')->default(0);
            $table->string('resolution')->default('4K Ultra HD');
            $table->boolean('has_thermal')->default(true);
            $table->string('status')->default('ACTIVE'); // ACTIVE, OFFLINE
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['zone_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
