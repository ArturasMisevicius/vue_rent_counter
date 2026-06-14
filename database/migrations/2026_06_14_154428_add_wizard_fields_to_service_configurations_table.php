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
        Schema::table('service_configurations', function (Blueprint $table): void {
            $table->string('service_name')->nullable()->after('utility_service_id');
            $table->string('service_type')->nullable()->after('service_name');
            $table->string('billing_method')->default('meter_based')->after('service_type');
            $table->string('unit')->nullable()->after('billing_method');
            $table->string('currency', 3)->default('EUR')->after('unit');
            $table->decimal('fixed_amount', 10, 2)->nullable()->after('currency');
            $table->string('billing_frequency')->nullable()->after('fixed_amount');
            $table->string('assignment_scope')->default('property')->after('billing_frequency');
            $table->boolean('tenant_visible')->default(false)->after('assignment_scope');
            $table->string('tenant_visible_name')->nullable()->after('tenant_visible');
            $table->text('tenant_visible_description')->nullable()->after('tenant_visible_name');
            $table->boolean('show_formula_to_tenant')->default(false)->after('tenant_visible_description');
            $table->boolean('show_provider_to_tenant')->default(false)->after('show_formula_to_tenant');
            $table->boolean('show_readings_to_tenant')->default(false)->after('show_provider_to_tenant');
            $table->text('internal_note')->nullable()->after('show_readings_to_tenant');
            $table->string('status')->default('active')->after('internal_note');
            $table->dateTime('starts_at')->nullable()->after('status');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->json('meter_rules')->nullable()->after('ends_at');
            $table->json('assignment_rules')->nullable()->after('meter_rules');
            $table->json('validation_result')->nullable()->after('assignment_rules');

            $table->index(['organization_id', 'status', 'billing_method'], 'service_configurations_org_status_method_index');
            $table->index(['service_type', 'billing_method'], 'service_configurations_type_method_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_configurations', function (Blueprint $table): void {
            $table->dropIndex('service_configurations_org_status_method_index');
            $table->dropIndex('service_configurations_type_method_index');
            $table->dropColumn([
                'service_name',
                'service_type',
                'billing_method',
                'unit',
                'currency',
                'fixed_amount',
                'billing_frequency',
                'assignment_scope',
                'tenant_visible',
                'tenant_visible_name',
                'tenant_visible_description',
                'show_formula_to_tenant',
                'show_provider_to_tenant',
                'show_readings_to_tenant',
                'internal_note',
                'status',
                'starts_at',
                'ends_at',
                'meter_rules',
                'assignment_rules',
                'validation_result',
            ]);
        });
    }
};
