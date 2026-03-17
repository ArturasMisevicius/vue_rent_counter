<?php

use App\Enums\IntegrationHealthStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('status')->default(IntegrationHealthStatus::HEALTHY->value)->index();
            $table->timestamp('checked_at')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('summary')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_health_checks');
    }
};
