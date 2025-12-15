<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taggables', function (Blueprint $table) {
            $table->foreignId('tagged_by')->nullable()->constrained('users')->after('tag_id');
            $table->timestamp('tagged_at')->useCurrent()->after('tagged_by');
            $table->json('context')->nullable()->after('tagged_at'); // Additional metadata
            
            $table->index(['tagged_by']);
            $table->index(['tagged_at']);
        });
    }

    public function down(): void
    {
        Schema::table('taggables', function (Blueprint $table) {
            $table->dropForeign(['tagged_by']);
            $table->dropIndex(['tagged_by']);
            $table->dropIndex(['tagged_at']);
            
            $table->dropColumn([
                'tagged_by',
                'tagged_at',
                'context'
            ]);
        });
    }
};