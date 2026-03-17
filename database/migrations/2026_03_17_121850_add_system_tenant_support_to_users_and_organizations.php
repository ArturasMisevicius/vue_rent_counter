<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'system_tenant_id')) {
                $table->foreignId('system_tenant_id')->nullable()->constrained('system_tenants')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->index();
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('organizations', 'system_tenant_id')) {
                $table->foreignId('system_tenant_id')->nullable()->constrained('system_tenants')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'system_tenant_id')) {
                $table->dropConstrainedForeignId('system_tenant_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'system_tenant_id')) {
                $table->dropConstrainedForeignId('system_tenant_id');
            }

            if (Schema::hasColumn('users', 'is_super_admin')) {
                $table->dropColumn('is_super_admin');
            }
        });
    }
};
