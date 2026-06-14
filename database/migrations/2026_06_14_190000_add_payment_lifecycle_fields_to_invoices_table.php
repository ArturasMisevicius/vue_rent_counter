<?php

use App\Enums\InvoicePaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'balance_amount')) {
                $table->decimal('balance_amount', 12, 2)->default(0)->after('paid_amount');
            }

            if (! Schema::hasColumn('invoices', 'payment_status')) {
                $table->string('payment_status')
                    ->default(InvoicePaymentStatus::UNPAID->value)
                    ->index()
                    ->after('status');
            }

            if (! Schema::hasColumn('invoices', 'overdue_at')) {
                $table->timestamp('overdue_at')->nullable()->after('payment_reference');
            }

            $table->index(['organization_id', 'payment_status'], 'invoices_org_payment_status_index');
            $table->index(['organization_id', 'due_date', 'payment_status'], 'invoices_org_due_payment_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_org_due_payment_index');
            $table->dropIndex('invoices_org_payment_status_index');

            if (Schema::hasColumn('invoices', 'overdue_at')) {
                $table->dropColumn('overdue_at');
            }

            if (Schema::hasColumn('invoices', 'payment_status')) {
                $table->dropColumn('payment_status');
            }

            if (Schema::hasColumn('invoices', 'balance_amount')) {
                $table->dropColumn('balance_amount');
            }
        });
    }
};
