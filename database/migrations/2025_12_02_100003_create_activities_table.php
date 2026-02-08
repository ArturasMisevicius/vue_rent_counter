<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Activity log with polymorphic relationships
     * Tracks all important actions across the system
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->morphs('subject'); // The model being acted upon
            $table->morphs('causer'); // Who performed the action (usually User)
            $table->json('properties')->nullable(); // Old/new values
            $table->string('event')->nullable(); // created, updated, deleted, etc.
            $table->string('batch_uuid')->nullable()->index(); // Group related activities
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');

            // Composite indexes for efficient queries
            $table->index(['subject_type', 'subject_id', 'created_at']);
            $table->index(['causer_type', 'causer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
