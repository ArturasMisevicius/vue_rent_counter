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
        Schema::table('property_assignments', function (Blueprint $table) {
            $table->string('status')->default('active')->after('unit_area_sqm');
            $table->boolean('is_primary')->default(true)->after('status');
            $table->unsignedSmallInteger('occupants_count')->nullable()->after('is_primary');
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('unassigned_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->after('created_by_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['organization_id', 'property_id', 'status', 'is_primary'], 'property_assignments_org_property_status_primary_idx');
            $table->index(['organization_id', 'tenant_user_id', 'status'], 'property_assignments_org_tenant_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_assignments', function (Blueprint $table) {
            $table->dropIndex('property_assignments_org_property_status_primary_idx');
            $table->dropIndex('property_assignments_org_tenant_status_idx');
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
            $table->dropColumn([
                'status',
                'is_primary',
                'occupants_count',
            ]);
        });
    }
};
