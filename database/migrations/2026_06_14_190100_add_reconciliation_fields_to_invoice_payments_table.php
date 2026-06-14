<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoice_payments', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('organization_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoice_payments', 'property_id')) {
                $table->foreignId('property_id')->nullable()->after('tenant_id')->constrained('properties')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoice_payments', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('amount');
            }

            if (! Schema::hasColumn('invoice_payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('method');
            }

            if (! Schema::hasColumn('invoice_payments', 'status')) {
                $table->string('status')->default(PaymentStatus::CONFIRMED->value)->index()->after('currency');
            }

            if (! Schema::hasColumn('invoice_payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('invoice_payments', 'submitted_by_user_id')) {
                $table->foreignId('submitted_by_user_id')->nullable()->after('recorded_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoice_payments', 'confirmed_by_user_id')) {
                $table->foreignId('confirmed_by_user_id')->nullable()->after('submitted_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoice_payments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by_user_id');
            }

            if (! Schema::hasColumn('invoice_payments', 'rejected_by_user_id')) {
                $table->foreignId('rejected_by_user_id')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoice_payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            }

            if (! Schema::hasColumn('invoice_payments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }

            if (! Schema::hasColumn('invoice_payments', 'voided_by_user_id')) {
                $table->foreignId('voided_by_user_id')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('invoice_payments', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('voided_by_user_id');
            }

            if (! Schema::hasColumn('invoice_payments', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('voided_at');
            }

            if (! Schema::hasColumn('invoice_payments', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('reference');
            }

            if (! Schema::hasColumn('invoice_payments', 'internal_note')) {
                $table->text('internal_note')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('invoice_payments', 'tenant_comment')) {
                $table->text('tenant_comment')->nullable()->after('internal_note');
            }

            if (! Schema::hasColumn('invoice_payments', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->index(['organization_id', 'status'], 'invoice_payments_org_status_index');
            $table->index(['invoice_id', 'status'], 'invoice_payments_invoice_status_index');
            $table->index(['tenant_id', 'status'], 'invoice_payments_tenant_status_index');
            $table->index(['property_id', 'status'], 'invoice_payments_property_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table): void {
            $table->dropIndex('invoice_payments_property_status_index');
            $table->dropIndex('invoice_payments_tenant_status_index');
            $table->dropIndex('invoice_payments_invoice_status_index');
            $table->dropIndex('invoice_payments_org_status_index');

            if (Schema::hasColumn('invoice_payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $columns = [
                'tenant_comment',
                'internal_note',
                'transaction_id',
                'void_reason',
                'voided_at',
                'rejection_reason',
                'rejected_at',
                'confirmed_at',
                'payment_date',
                'status',
                'payment_method',
                'currency',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('invoice_payments', $column)) {
                    $table->dropColumn($column);
                }
            }

            foreach ([
                'voided_by_user_id',
                'rejected_by_user_id',
                'confirmed_by_user_id',
                'submitted_by_user_id',
                'property_id',
                'tenant_id',
            ] as $foreignColumn) {
                if (Schema::hasColumn('invoice_payments', $foreignColumn)) {
                    $table->dropConstrainedForeignId($foreignColumn);
                }
            }
        });
    }
};
