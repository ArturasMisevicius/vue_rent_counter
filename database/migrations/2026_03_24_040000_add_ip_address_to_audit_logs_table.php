<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('description');
            $table->index(['occurred_at', 'id'], 'audit_logs_occurred_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_occurred_at_id_index');
            $table->dropColumn('ip_address');
        });
    }
};
