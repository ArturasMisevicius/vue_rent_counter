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
        // Add composite index for meter reading lookups
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->index(['meter_id', 'reading_date', 'zone'], 'meter_readings_lookup_index');
            $table->index('reading_date', 'meter_readings_date_index');
        });

        // Add composite index for tariff lookups
        Schema::table('tariffs', function (Blueprint $table) {
            $table->index(['provider_id', 'active_from', 'active_until'], 'tariffs_active_lookup_index');
        });

        // Add composite index for invoice lookups
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['tenant_renter_id', 'billing_period_start'], 'invoices_tenant_period_index');
            $table->index('status', 'invoices_status_index');
        });

        // Add index for meter property lookups
        Schema::table('meters', function (Blueprint $table) {
            $table->index('property_id', 'meters_property_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropIndex('meter_readings_lookup_index');
            $table->dropIndex('meter_readings_date_index');
        });

        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropIndex('tariffs_active_lookup_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_tenant_period_index');
            $table->dropIndex('invoices_status_index');
        });

        Schema::table('meters', function (Blueprint $table) {
            $table->dropIndex('meters_property_index');
        });
    }
};
