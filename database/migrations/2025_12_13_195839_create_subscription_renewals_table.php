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
        Schema::create('subscription_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who performed the renewal (null for automatic)
            $table->enum('method', ['manual', 'automatic'])->default('manual');
            $table->enum('period', ['monthly', 'quarterly', 'annually'])->default('annually');
            $table->datetime('old_expires_at'); // Previous expiry date
            $table->datetime('new_expires_at'); // New expiry date
            $table->integer('duration_days'); // Number of days extended
            $table->text('notes')->nullable(); // Optional notes about the renewal
            $table->timestamps();

            $table->index(['subscription_id', 'created_at']);
            $table->index(['method', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_renewals');
    }
};
