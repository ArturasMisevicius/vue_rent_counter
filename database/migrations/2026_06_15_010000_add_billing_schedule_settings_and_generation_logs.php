<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->boolean('auto_generation_enabled')->default(false)->after('notification_preferences');
            $table->string('billing_frequency', 32)->default('monthly')->after('auto_generation_enabled');
            $table->unsignedTinyInteger('invoice_generation_day')->default(1)->after('billing_frequency');
            $table->unsignedTinyInteger('reading_deadline_day')->default(5)->after('invoice_generation_day');
            $table->unsignedSmallInteger('payment_due_days')->default(14)->after('reading_deadline_day');
            $table->boolean('send_created_notification')->default(true)->after('payment_due_days');
            $table->boolean('send_reminders')->default(true)->after('send_created_notification');
            $table->json('reminder_days_before_deadline')->nullable()->after('send_reminders');
            $table->string('timezone', 64)->default('UTC')->after('reminder_days_before_deadline');
            $table->string('default_currency', 3)->default('EUR')->after('timezone');

            $table->index(
                ['auto_generation_enabled', 'billing_frequency', 'invoice_generation_day'],
                'org_settings_billing_schedule_index',
            );
        });

        Schema::create('billing_generation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->index();
            $table->string('status', 32)->index();
            $table->boolean('dry_run')->default(false);
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('invoice_generation_date')->nullable();
            $table->date('reading_submission_deadline')->nullable();
            $table->date('payment_due_date')->nullable();
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('notified_tenants_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'billing_period_id'], 'billing_generation_logs_org_period_index');
            $table->index(['organization_id', 'created_at'], 'billing_generation_logs_org_created_index');
        });

        Schema::create('billing_generation_log_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_generation_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 32)->index();
            $table->string('code', 80)->index();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(
                ['organization_id', 'billing_period_id', 'level'],
                'billing_generation_log_items_org_period_level_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_generation_log_items');
        Schema::dropIfExists('billing_generation_logs');

        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->dropIndex('org_settings_billing_schedule_index');
            $table->dropColumn([
                'auto_generation_enabled',
                'billing_frequency',
                'invoice_generation_day',
                'reading_deadline_day',
                'payment_due_days',
                'send_created_notification',
                'send_reminders',
                'reminder_days_before_deadline',
                'timezone',
                'default_currency',
            ]);
        });
    }
};
