<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fire_incident_id')->constrained('fire_incidents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->string('action_title')->nullable();
            $table->text('note')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('duration_from_previous_seconds')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['fire_incident_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_timelines');
    }
};
