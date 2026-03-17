<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_notification_id')->constrained('platform_notifications')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('delivery_status')->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['platform_notification_id', 'delivery_status']);
            $table->index(['organization_id', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notification_recipients');
    }
};
