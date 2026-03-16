<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->integer('depth')->default(0)->after('parent_id');
                $table->string('path')->nullable()->after('depth'); // Materialized path for efficient queries
                $table->integer('sort_order')->default(0)->after('path');
                $table->json('mentions')->nullable()->after('sort_order'); // @user mentions
                $table->boolean('is_resolved')->default(false)->after('mentions');
                $table->foreignId('resolved_by')->nullable()->constrained('users')->after('is_resolved');
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
                
                $table->index(['commentable_type', 'commentable_id', 'is_resolved']);
                $table->index(['path']);
                $table->index(['parent_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropForeign(['resolved_by']);
                $table->dropIndex(['commentable_type', 'commentable_id', 'is_resolved']);
                $table->dropIndex(['path']);
                $table->dropIndex(['parent_id', 'sort_order']);
                
                $table->dropColumn([
                    'depth',
                    'path',
                    'sort_order',
                    'mentions',
                    'is_resolved',
                    'resolved_by',
                    'resolved_at'
                ]);
            });
        }
    }
};