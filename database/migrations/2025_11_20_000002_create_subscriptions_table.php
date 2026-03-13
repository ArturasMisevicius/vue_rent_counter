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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to users table (admin only)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Plan type
            $table->enum('plan_type', ['basic', 'professional', 'enterprise'])
                  ->default('basic');
            
            // Subscription status
            $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])
                  ->default('active');
            
            // Subscription dates
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            
            // Subscription limits
            $table->integer('max_properties')->default(10);
            $table->integer('max_tenants')->default(50);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status'], 'subscriptions_user_status_index');
            $table->index('expires_at', 'subscriptions_expires_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
