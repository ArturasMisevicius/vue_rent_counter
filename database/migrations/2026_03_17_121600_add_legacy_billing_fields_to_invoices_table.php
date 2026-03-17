<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('invoices', 'snapshot_data')) {
                $table->json('snapshot_data')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'snapshot_created_at')) {
                $table->timestamp('snapshot_created_at')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'items')) {
                $table->json('items')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'generated_at')) {
                $table->timestamp('generated_at')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'generated_by')) {
                $table->string('generated_by')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'approval_status')) {
                $table->string('approval_status')->default('pending')->index();
            }

            if (! Schema::hasColumn('invoices', 'automation_level')) {
                $table->string('automation_level')->default('manual')->index();
            }

            if (! Schema::hasColumn('invoices', 'approval_deadline')) {
                $table->timestamp('approval_deadline')->nullable()->index();
            }

            if (! Schema::hasColumn('invoices', 'approval_metadata')) {
                $table->json('approval_metadata')->nullable();
            }

            if (! Schema::hasColumn('invoices', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoices', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('invoices', 'payment_reference') ? 'payment_reference' : null,
                Schema::hasColumn('invoices', 'paid_amount') ? 'paid_amount' : null,
                Schema::hasColumn('invoices', 'snapshot_data') ? 'snapshot_data' : null,
                Schema::hasColumn('invoices', 'snapshot_created_at') ? 'snapshot_created_at' : null,
                Schema::hasColumn('invoices', 'items') ? 'items' : null,
                Schema::hasColumn('invoices', 'generated_at') ? 'generated_at' : null,
                Schema::hasColumn('invoices', 'generated_by') ? 'generated_by' : null,
                Schema::hasColumn('invoices', 'approval_status') ? 'approval_status' : null,
                Schema::hasColumn('invoices', 'automation_level') ? 'automation_level' : null,
                Schema::hasColumn('invoices', 'approval_deadline') ? 'approval_deadline' : null,
                Schema::hasColumn('invoices', 'approval_metadata') ? 'approval_metadata' : null,
                Schema::hasColumn('invoices', 'approved_at') ? 'approved_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
