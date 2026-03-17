<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['organization_id', 'role']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->index(['organization_id']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['organization_id', 'billing_period_start', 'id']);
            $table->index(['organization_id', 'due_date', 'id']);
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->index(['organization_id', 'reading_date', 'id']);
        });

        Schema::table('property_assignments', function (Blueprint $table): void {
            $table->index(['organization_id', 'unassigned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'role']);
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['organization_id']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'billing_period_start', 'id']);
            $table->dropIndex(['organization_id', 'due_date', 'id']);
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'reading_date', 'id']);
        });

        Schema::table('property_assignments', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'unassigned_at']);
        });
    }
};
