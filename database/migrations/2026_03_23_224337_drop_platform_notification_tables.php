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
        Schema::dropIfExists('platform_notification_deliveries');
        Schema::dropIfExists('platform_notification_recipients');
        Schema::dropIfExists('platform_notifications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('platform_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('severity');
            $table->string('status');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_notification_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_notification_id')->constrained('platform_notifications')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('email');
            $table->string('delivery_status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_notification_id')->constrained('platform_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }
};
