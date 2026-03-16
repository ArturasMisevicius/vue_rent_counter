<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('billing_period_end');
            $table->timestamp('paid_at')->nullable()->after('finalized_at');
        });

        // Backfill a sensible default due date (15 days after billing period end) for existing records.
        DB::table('invoices')
            ->whereNull('due_date')
            ->update([
                'due_date' => DB::raw("date(billing_period_end, '+15 days')")
            ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'paid_at']);
        });
    }
};
