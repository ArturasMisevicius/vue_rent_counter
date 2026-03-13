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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('system_tenant_id')->nullable()->after('id')->constrained('system_tenants')->onDelete('cascade');
            $table->boolean('is_super_admin')->default(false)->after('system_tenant_id');
            
            // Indexes for performance
            $table->index(['system_tenant_id']);
            $table->index(['is_super_admin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['system_tenant_id']);
            $table->dropIndex(['system_tenant_id']);
            $table->dropIndex(['is_super_admin']);
            $table->dropColumn(['system_tenant_id', 'is_super_admin']);
        });
    }
};
