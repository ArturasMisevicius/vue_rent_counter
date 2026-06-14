<?php

use App\Enums\PropertyOccupancyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->string('occupancy_status')
                ->default(PropertyOccupancyStatus::VACANT->value)
                ->after('floor_area_sqm')
                ->index();

            $table->index(['organization_id', 'occupancy_status'], 'properties_org_occupancy_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropIndex('properties_org_occupancy_status_idx');
            $table->dropColumn('occupancy_status');
        });
    }
};
