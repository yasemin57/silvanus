<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_logs', function (Blueprint $table) {
            $table->id();
            $table->string('component'); // EDGE_SERVER, SENSORS, CAMERAS, FCM, CENTRAL_DB
            $table->string('status');    // HEALTHY, DEGRADED, DOWN
            $table->string('message')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->index(['component', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_logs');
    }
};
