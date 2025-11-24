<?php

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
        Schema::create('system_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // database, backup, queue, storage, cache
            $table->string('metric_name');
            $table->json('value');
            $table->string('status'); // healthy, warning, danger
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['metric_type', 'checked_at'], 'health_metrics_type_checked_index');
            $table->index('status', 'health_metrics_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_health_metrics');
    }
};
