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
            // Add index on tenant_id for multi-tenant query optimization
            if (!Schema::hasColumn('buildings', 'tenant_id')) {
                return;
            }
            
            // Check if index already exists using raw query
            $indexExists = collect(DB::select("PRAGMA index_list('buildings')"))
                ->pluck('name')
                ->contains('buildings_tenant_id_index');
            
            if (!$indexExists) {
                $table->index('tenant_id', 'buildings_tenant_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropIndex('buildings_tenant_id_index');
        });
    }
};
