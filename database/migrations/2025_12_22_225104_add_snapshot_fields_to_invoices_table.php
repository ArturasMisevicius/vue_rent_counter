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
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('snapshot_data')->nullable()->after('status');
            $table->timestamp('snapshot_created_at')->nullable()->after('snapshot_data');
            $table->json('items')->nullable()->after('total_amount');
            $table->timestamp('generated_at')->nullable()->after('finalized_at');
            $table->string('generated_by')->nullable()->after('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_data',
                'snapshot_created_at',
                'items',
                'generated_at',
                'generated_by',
            ]);
        });
    }
};
