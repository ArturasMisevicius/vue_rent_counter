<?php

use App\Enums\MeterReadingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('submitted_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('billing_period_id')
                ->nullable()
                ->after('property_id')
                ->constrained('billing_periods')
                ->nullOnDelete();
            $table->decimal('previous_value', 12, 3)->nullable()->after('reading_date');
            $table->decimal('current_value', 12, 3)->nullable()->after('previous_value');
            $table->decimal('consumption', 12, 3)->nullable()->after('current_value');
            $table->string('status')->default(MeterReadingStatus::APPROVED->value)->after('validation_status')->index();
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->foreignId('approved_by_user_id')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->foreignId('rejected_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->foreignId('corrected_by_user_id')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->text('correction_reason')->nullable()->after('corrected_by_user_id');
            $table->text('tenant_comment')->nullable()->after('correction_reason');
            $table->timestamp('voided_at')->nullable()->after('tenant_comment');

            $table->index(['organization_id', 'tenant_id', 'property_id', 'billing_period_id'], 'meter_readings_inbox_scope_idx');
            $table->index(['invoice_id', 'status'], 'meter_readings_invoice_status_idx');
            $table->index(['meter_id', 'billing_period_id', 'tenant_id', 'status'], 'meter_readings_active_scope_idx');
        });

        Schema::create('meter_reading_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meter_reading_id')->constrained('meter_readings')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('billing_period_id')->nullable()->constrained('billing_periods')->nullOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('event')->index();
            $table->decimal('previous_value', 12, 3)->nullable();
            $table->decimal('current_value', 12, 3)->nullable();
            $table->decimal('consumption', 12, 3)->nullable();
            $table->string('status')->nullable()->index();
            $table->date('reading_date')->nullable();
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['meter_reading_id', 'version'], 'meter_reading_versions_reading_version_unique');
            $table->index(['organization_id', 'invoice_id'], 'meter_reading_versions_org_invoice_idx');
            $table->index(['billing_period_id', 'status'], 'meter_reading_versions_period_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_reading_versions');

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->dropIndex('meter_readings_active_scope_idx');
            $table->dropIndex('meter_readings_invoice_status_idx');
            $table->dropIndex('meter_readings_inbox_scope_idx');
            $table->dropConstrainedForeignId('corrected_by_user_id');
            $table->dropConstrainedForeignId('rejected_by_user_id');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropConstrainedForeignId('billing_period_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn([
                'previous_value',
                'current_value',
                'consumption',
                'status',
                'submitted_at',
                'approved_at',
                'rejected_at',
                'rejection_reason',
                'correction_reason',
                'tenant_comment',
                'voided_at',
            ]);
        });
    }
};
