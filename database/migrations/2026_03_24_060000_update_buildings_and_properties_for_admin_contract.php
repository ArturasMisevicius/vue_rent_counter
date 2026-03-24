<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('city')->nullable()->change();
            $table->string('postal_code')->nullable()->change();
            $table->string('country_code', 2)->nullable()->change();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->integer('floor')->nullable()->after('name');
            $table->string('unit_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('floor');
            $table->string('unit_number')->nullable(false)->change();
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->string('city')->nullable(false)->change();
            $table->string('postal_code')->nullable(false)->change();
            $table->string('country_code', 2)->nullable(false)->change();
        });
    }
};
