<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhance the existing property_tenant pivot table with additional data
     */
    public function up(): void
    {
        Schema::table('property_tenant', function (Blueprint $table) {
            // Add additional pivot data if not already present
            if (!Schema::hasColumn('property_tenant', 'monthly_rent')) {
                $table->decimal('monthly_rent', 10, 2)->nullable()->after('vacated_at');
            }
            if (!Schema::hasColumn('property_tenant', 'deposit_amount')) {
                $table->decimal('deposit_amount', 10, 2)->nullable()->after('monthly_rent');
            }
            if (!Schema::hasColumn('property_tenant', 'lease_type')) {
                $table->string('lease_type')->default('standard')->after('deposit_amount'); // standard, short-term, commercial
            }
            if (!Schema::hasColumn('property_tenant', 'notes')) {
                $table->text('notes')->nullable()->after('lease_type');
            }
            if (!Schema::hasColumn('property_tenant', 'assigned_by')) {
                $table->unsignedBigInteger('assigned_by')->nullable()->after('notes');
                $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_tenant', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn([
                'monthly_rent',
                'deposit_amount',
                'lease_type',
                'notes',
                'assigned_by',
            ]);
        });
    }
};
