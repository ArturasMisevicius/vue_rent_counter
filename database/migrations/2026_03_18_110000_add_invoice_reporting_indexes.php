<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['organization_id', 'billing_period_end', 'id']);
            $table->index(['organization_id', 'paid_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'billing_period_end', 'id']);
            $table->dropIndex(['organization_id', 'paid_at', 'id']);
        });
    }
};
