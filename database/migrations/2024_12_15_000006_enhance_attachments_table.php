<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attachments')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->string('category')->nullable()->after('description'); // invoice, photo, document, etc.
                $table->json('processing_status')->nullable()->after('category'); // For image/video processing
                $table->string('thumbnail_path')->nullable()->after('processing_status');
                $table->json('exif_data')->nullable()->after('thumbnail_path');
                $table->boolean('is_public')->default(false)->after('exif_data');
                $table->timestamp('expires_at')->nullable()->after('is_public');
                
                $table->index(['attachable_type', 'attachable_id', 'category']);
                $table->index(['category']);
                $table->index(['is_public']);
                $table->index(['expires_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attachments')) {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropIndex(['attachable_type', 'attachable_id', 'category']);
                $table->dropIndex(['category']);
                $table->dropIndex(['is_public']);
                $table->dropIndex(['expires_at']);
                
                $table->dropColumn([
                    'category',
                    'processing_status',
                    'thumbnail_path',
                    'exif_data',
                    'is_public',
                    'expires_at'
                ]);
            });
        }
    }
};