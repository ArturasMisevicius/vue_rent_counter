<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds audit trail fields to faqs table:
     * - created_by: User who created the FAQ
     * - updated_by: User who last updated the FAQ
     * - deleted_by: User who soft-deleted the FAQ
     * - deleted_at: Soft delete timestamp
     */
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            // Audit trail fields
            $table->foreignId('created_by')->nullable()->after('is_published')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            
            // Soft deletes
            $table->softDeletes()->after('updated_at');
            
            // Indexes for performance
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);
            
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['deleted_at']);
            
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
            $table->dropSoftDeletes();
        });
    }
};
