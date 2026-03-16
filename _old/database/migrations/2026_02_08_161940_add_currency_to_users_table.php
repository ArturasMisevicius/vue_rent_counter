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
        if (! Schema::hasColumn('users', 'currency')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('currency', 3)->default('EUR')->after('organization_name');
            });
        }

        DB::table('users')
            ->whereNull('currency')
            ->update(['currency' => 'EUR']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'currency')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};
