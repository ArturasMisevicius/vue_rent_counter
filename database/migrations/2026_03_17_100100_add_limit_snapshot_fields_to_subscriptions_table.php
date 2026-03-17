<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->unsignedInteger('property_limit_snapshot')->nullable()->after('is_trial');
            $table->unsignedInteger('tenant_limit_snapshot')->nullable()->after('property_limit_snapshot');
            $table->unsignedInteger('meter_limit_snapshot')->nullable()->after('tenant_limit_snapshot');
            $table->unsignedInteger('invoice_limit_snapshot')->nullable()->after('meter_limit_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'property_limit_snapshot',
                'tenant_limit_snapshot',
                'meter_limit_snapshot',
                'invoice_limit_snapshot',
            ]);
        });
    }
};
