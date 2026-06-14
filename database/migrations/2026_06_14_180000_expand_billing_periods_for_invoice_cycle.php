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
        Schema::table('billing_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('billing_periods', 'reading_submission_deadline')) {
                $table->date('reading_submission_deadline')->nullable()->after('ends_at');
            }

            if (! Schema::hasColumn('billing_periods', 'invoice_generation_date')) {
                $table->date('invoice_generation_date')->nullable()->after('reading_submission_deadline');
            }

            if (! Schema::hasColumn('billing_periods', 'payment_due_date')) {
                $table->date('payment_due_date')->nullable()->after('invoice_generation_date');
            }

            $table->index(
                ['organization_id', 'invoice_generation_date'],
                'billing_periods_org_generation_date_index',
            );
            $table->index(
                ['organization_id', 'reading_submission_deadline'],
                'billing_periods_org_reading_deadline_index',
            );
        });

        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'billing_period_id')) {
                $table
                    ->foreignId('billing_period_id')
                    ->nullable()
                    ->after('organization_id')
                    ->constrained('billing_periods')
                    ->nullOnDelete();
            }

            $table->index(
                ['organization_id', 'billing_period_id', 'tenant_user_id', 'property_id'],
                'invoices_org_period_tenant_property_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('invoices', 'billing_period_id')) {
                $table->dropIndex('invoices_org_period_tenant_property_index');
                $table->dropConstrainedForeignId('billing_period_id');
            }
        });

        Schema::table('billing_periods', function (Blueprint $table): void {
            $table->dropIndex('billing_periods_org_generation_date_index');
            $table->dropIndex('billing_periods_org_reading_deadline_index');

            $columns = collect([
                'payment_due_date',
                'invoice_generation_date',
                'reading_submission_deadline',
            ])
                ->filter(fn (string $column): bool => Schema::hasColumn('billing_periods', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
