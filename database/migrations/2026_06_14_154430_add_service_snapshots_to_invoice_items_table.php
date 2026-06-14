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
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->foreignId('service_configuration_id')->nullable()->after('invoice_id')->constrained('service_configurations')->nullOnDelete();
            $table->foreignId('utility_service_id')->nullable()->after('service_configuration_id')->constrained('utility_services')->nullOnDelete();
            $table->foreignId('tariff_id')->nullable()->after('utility_service_id')->constrained('tariffs')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->after('tariff_id')->constrained('providers')->nullOnDelete();
            $table->json('service_snapshot')->nullable()->after('meter_reading_snapshot');
            $table->json('tariff_snapshot')->nullable()->after('service_snapshot');
            $table->json('provider_snapshot')->nullable()->after('tariff_snapshot');

            $table->index(['service_configuration_id', 'invoice_id'], 'invoice_items_service_configuration_invoice_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex('invoice_items_service_configuration_invoice_index');
            $table->dropConstrainedForeignId('provider_id');
            $table->dropConstrainedForeignId('tariff_id');
            $table->dropConstrainedForeignId('utility_service_id');
            $table->dropConstrainedForeignId('service_configuration_id');
            $table->dropColumn([
                'service_snapshot',
                'tariff_snapshot',
                'provider_snapshot',
            ]);
        });
    }
};
