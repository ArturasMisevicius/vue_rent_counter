<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('name')->nullable()->after('tenant_id');
        });

        // Backfill existing records so building names are immediately available to tenants
        DB::table('buildings')
            ->whereNull('name')
            ->update(['name' => DB::raw('address')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
