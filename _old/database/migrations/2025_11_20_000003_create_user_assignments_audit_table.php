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
        Schema::create('user_assignments_audit', function (Blueprint $table) {
            $table->id();
            
            // User being acted upon
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Current property assignment
            $table->foreignId('property_id')
                  ->nullable()
                  ->constrained('properties')
                  ->onDelete('set null');
            
            // Previous property assignment (for reassignments)
            $table->foreignId('previous_property_id')
                  ->nullable()
                  ->constrained('properties')
                  ->onDelete('set null');
            
            // User who performed the action
            $table->foreignId('performed_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Action type
            $table->enum('action', ['created', 'assigned', 'reassigned', 'deactivated', 'reactivated']);
            
            // Optional reason for the action
            $table->text('reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at'], 'user_assignments_audit_user_created_index');
            $table->index('performed_by', 'user_assignments_audit_performed_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_assignments_audit');
    }
};
