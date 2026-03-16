<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integration_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service_name', 100)->index();
            $table->string('endpoint', 500);
            $table->enum('status', [
                'healthy',
                'degraded', 
                'unhealthy',
                'circuit_open',
                'maintenance',
                'unknown'
            ])->index();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();

            // Indexes for performance
            $table->index(['service_name', 'status']);
            $table->index(['service_name', 'checked_at']);
            $table->index(['checked_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_health_checks');
    }
};