<?php

declare(strict_types=1);

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
        Schema::table('tariffs', function (Blueprint $table) {
            // Add remote_id column for external system integration
            $table->string('remote_id', 255)->nullable()->after('provider_id');
            
            // Make provider_id nullable to support manual tariff entry
            $table->foreignId('provider_id')->nullable()->change();
            
            // Add index for remote_id lookups
            $table->index('remote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropIndex(['remote_id']);
            $table->dropColumn('remote_id');
            
            // Restore provider_id as required
            $table->foreignId('provider_id')->nullable(false)->change();
        });
    }
};
