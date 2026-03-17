<?php

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('severity')->default(PlatformNotificationSeverity::INFO->value)->index();
            $table->string('status')->default(PlatformNotificationStatus::DRAFT->value)->index();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
    }
};
