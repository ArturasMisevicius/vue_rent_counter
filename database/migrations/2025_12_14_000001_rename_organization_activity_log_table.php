<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organization_activity_log') && !Schema::hasTable('organization_activity_logs')) {
            Schema::rename('organization_activity_log', 'organization_activity_logs');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('organization_activity_logs') && !Schema::hasTable('organization_activity_log')) {
            Schema::rename('organization_activity_logs', 'organization_activity_log');
        }
    }
};

