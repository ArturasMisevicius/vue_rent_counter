<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->expandMySqlPropertyTypes();
            $this->expandMySqlMeterTypes();
            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildPropertiesTableForSqlite(['apartment', 'house', 'commercial']);
            $this->rebuildMetersTableForSqlite(['electricity', 'water_cold', 'water_hot', 'heating', 'custom']);
            return;
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE properties MODIFY type ENUM('apartment','house') NOT NULL");
            DB::statement("ALTER TABLE meters MODIFY type ENUM('electricity','water_cold','water_hot','heating') NOT NULL");
            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildPropertiesTableForSqlite(['apartment', 'house']);
            $this->rebuildMetersTableForSqlite(['electricity', 'water_cold', 'water_hot', 'heating']);
            return;
        }
    }

    private function expandMySqlPropertyTypes(): void
    {
        DB::statement("ALTER TABLE properties MODIFY type ENUM('apartment','house','commercial') NOT NULL");
    }

    private function expandMySqlMeterTypes(): void
    {
        DB::statement("ALTER TABLE meters MODIFY type ENUM('electricity','water_cold','water_hot','heating','custom') NOT NULL");
    }

    /**
     * SQLite enum columns are implemented using CHECK constraints, so we rebuild the table
     * to adjust allowed values while preserving data, foreign keys, and indexes.
     *
     * @param array<int, string> $allowedTypes
     */
    private function rebuildPropertiesTableForSqlite(array $allowedTypes): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('properties_tmp');

        Schema::create('properties_tmp', function (Blueprint $table) use ($allowedTypes): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->text('address');
            $table->enum('type', $allowedTypes);
            $table->decimal('area_sqm', 8, 2);
            $table->string('unit_number')->nullable();
            $table->foreignId('building_id')->nullable()->constrained('buildings')->onDelete('set null');
            $table->timestamps();
        });

        DB::statement(
            'INSERT INTO properties_tmp (id, tenant_id, address, type, area_sqm, unit_number, building_id, created_at, updated_at)
             SELECT id, tenant_id, address, type, area_sqm, unit_number, building_id, created_at, updated_at FROM properties'
        );

        Schema::drop('properties');
        Schema::rename('properties_tmp', 'properties');

        Schema::table('properties', function (Blueprint $table): void {
            $table->index('tenant_id');

            $table->index('created_at', 'properties_created_at_index');
            $table->index(['tenant_id', 'created_at'], 'properties_tenant_created_index');
            $table->index('building_id', 'properties_building_id_index');

            $table->index('type', 'properties_type_index');
            $table->index('area_sqm', 'properties_area_index');
            $table->index(['building_id', 'type'], 'properties_building_type_index');
            $table->index(['tenant_id', 'type'], 'properties_tenant_type_index');
            $table->index(['building_id', 'address'], 'properties_building_address_index');

            $table->index(['building_id', 'area_sqm'], 'properties_building_area_idx');
            $table->index(['tenant_id', 'building_id'], 'properties_tenant_building_idx');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * @param array<int, string> $allowedTypes
     */
    private function rebuildMetersTableForSqlite(array $allowedTypes): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('meters_tmp');

        Schema::create('meters_tmp', function (Blueprint $table) use ($allowedTypes): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('serial_number');
            $table->enum('type', $allowedTypes);
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->date('installation_date');
            $table->boolean('supports_zones')->default(false);
            $table->json('reading_structure')->nullable();
            $table->foreignId('service_configuration_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        DB::statement(
            'INSERT INTO meters_tmp (id, tenant_id, serial_number, type, property_id, installation_date, supports_zones, reading_structure, service_configuration_id, created_at, updated_at)
             SELECT id, tenant_id, serial_number, type, property_id, installation_date, supports_zones, reading_structure, service_configuration_id, created_at, updated_at FROM meters'
        );

        Schema::drop('meters');
        Schema::rename('meters_tmp', 'meters');

        Schema::table('meters', function (Blueprint $table): void {
            $table->index('tenant_id');
            $table->unique('serial_number');

            $table->index('type', 'meters_type_index');
            $table->index(['property_id', 'type'], 'meters_property_type_index');
            $table->index('installation_date', 'meters_installation_date_index');
            $table->index('created_at', 'meters_created_at_index');

            $table->index('property_id', 'meters_property_index');
            $table->index('service_configuration_id');

            $table->index(['service_configuration_id', 'tenant_id'], 'idx_meters_service_config');
            $table->index(['property_id', 'type', 'tenant_id'], 'idx_meters_property_type');
        });

        Schema::enableForeignKeyConstraints();
    }
};

