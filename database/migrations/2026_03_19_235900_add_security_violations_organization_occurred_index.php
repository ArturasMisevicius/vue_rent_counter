<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'security_violations_org_occurred_idx';

    public function up(): void
    {
        Schema::table('security_violations', function (Blueprint $table): void {
            $table->index(['organization_id', 'occurred_at'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        Schema::table('security_violations', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX_NAME);
        });
    }
};
