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
        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('target_type'); // 'all', 'plan', 'organization'
            $table->json('target_criteria')->nullable(); // plan names or organization IDs
            $table->string('status')->default('draft'); // draft, scheduled, sent, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('delivery_stats')->nullable(); // email sent count, read receipts, etc.
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
            $table->index(['target_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
    }
};
