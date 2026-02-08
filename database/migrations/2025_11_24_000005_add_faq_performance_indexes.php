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
        Schema::table('faqs', function (Blueprint $table) {
            // Composite index for common query pattern (published + order)
            $table->index(['is_published', 'display_order'], 'faqs_published_order_index');
            
            // Index for search queries
            $table->index('question', 'faqs_question_index');
            
            // Note: deleted_at index is automatically created by softDeletes() in the create_faqs_table migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('faqs_published_order_index');
            $table->dropIndex('faqs_question_index');
        });
    }
};
