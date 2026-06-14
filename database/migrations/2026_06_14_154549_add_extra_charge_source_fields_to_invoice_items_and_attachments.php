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
        if (! Schema::hasColumn('attachments', 'tenant_visible')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->boolean('tenant_visible')->default(false)->after('document_type');
                $table->index(['attachable_type', 'attachable_id', 'tenant_visible'], 'attachments_attachable_visible_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('attachments', 'tenant_visible')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->dropIndex('attachments_attachable_visible_index');
                $table->dropColumn('tenant_visible');
            });
        }
    }
};
