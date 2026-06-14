<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_configurations', function (Blueprint $table): void {
            $table
                ->text('invoice_description')
                ->nullable()
                ->after('custom_formula');
        });
    }

    public function down(): void
    {
        Schema::table('service_configurations', function (Blueprint $table): void {
            $table->dropColumn('invoice_description');
        });
    }
};
