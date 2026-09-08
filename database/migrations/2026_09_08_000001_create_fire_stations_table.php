<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fire_stations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('coverage_radius_km', 8, 2)->default(25.00);
            $table->integer('active_units_count')->default(4);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fire_stations');
    }
};
