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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('approval_status')->default('pending')->after('snapshot_created_at');
            $table->string('automation_level')->default('manual')->after('approval_status');
            $table->timestamp('approval_deadline')->nullable()->after('automation_level');
            $table->json('approval_metadata')->nullable()->after('approval_deadline');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_metadata');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['approval_status']);
            $table->index(['automation_level']);
            $table->index(['approval_deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['approval_status']);
            $table->dropIndex(['automation_level']);
            $table->dropIndex(['approval_deadline']);
            
            $table->dropColumn([
                'approval_status',
                'automation_level',
                'approval_deadline',
                'approval_metadata',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};