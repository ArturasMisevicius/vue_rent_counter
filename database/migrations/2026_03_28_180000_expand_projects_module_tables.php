<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->string('project_reference_prefix')->default('PROJ-')->after('invoice_footer');
            $table->unsignedInteger('project_reference_sequence')->default(0)->after('project_reference_prefix');
            $table->string('project_completion_mode')->default('manual')->after('project_reference_sequence');
            $table->unsignedTinyInteger('project_budget_alert_threshold_percent')->default(10)->after('project_completion_mode');
            $table->unsignedTinyInteger('project_schedule_alert_threshold_days')->default(30)->after('project_budget_alert_threshold_percent');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->string('reference_number', 50)->nullable()->after('name');
            $table->foreignId('manager_id')->nullable()->after('assigned_to_user_id')->constrained('users')->nullOnDelete();
            $table->decimal('budget_amount', 12, 2)->nullable()->after('priority');
            $table->boolean('cost_passed_to_tenant')->default(false)->after('actual_cost');
            $table->date('estimated_start_date')->nullable()->after('start_date');
            $table->date('actual_start_date')->nullable()->after('estimated_start_date');
            $table->date('estimated_end_date')->nullable()->after('due_date');
            $table->date('actual_end_date')->nullable()->after('estimated_end_date');
            $table->unsignedTinyInteger('completion_percentage')->default(0)->after('actual_end_date');
            $table->boolean('requires_approval')->default(false)->after('completion_percentage');
            $table->timestamp('approved_at')->nullable()->after('requires_approval');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->string('external_contractor')->nullable()->after('cancellation_reason');
            $table->string('contractor_contact')->nullable()->after('external_contractor');
            $table->string('contractor_reference', 100)->nullable()->after('contractor_contact');
            $table->text('notes')->nullable()->after('contractor_reference');
            $table->softDeletes()->after('metadata');

            $table->unique(['organization_id', 'reference_number']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('hold_reason')->nullable()->after('description');
            $table->text('cancellation_note')->nullable()->after('hold_reason');
            $table->softDeletes()->after('checklist');
        });

        Schema::table('time_entries', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('task_id')->constrained()->nullOnDelete();
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('hours');
            $table->decimal('cost_amount', 12, 2)->default(0)->after('hourly_rate');
            $table->string('approval_status')->default('approved')->after('description');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->softDeletes()->after('logged_at');

            $table->index(['organization_id', 'project_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
            $table->json('metadata')->nullable()->after('meter_reading_snapshot');
            $table->timestamp('voided_at')->nullable()->after('metadata');
            $table->text('void_reason')->nullable()->after('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['metadata', 'voided_at', 'void_reason']);
        });

        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'project_id']);
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn([
                'hourly_rate',
                'cost_amount',
                'approval_status',
                'approved_at',
                'rejected_at',
                'rejection_reason',
                'deleted_at',
            ]);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn(['hold_reason', 'cancellation_note', 'deleted_at']);
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'reference_number']);
            $table->dropConstrainedForeignId('manager_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'reference_number',
                'budget_amount',
                'cost_passed_to_tenant',
                'estimated_start_date',
                'actual_start_date',
                'estimated_end_date',
                'actual_end_date',
                'completion_percentage',
                'requires_approval',
                'approved_at',
                'cancelled_at',
                'cancellation_reason',
                'external_contractor',
                'contractor_contact',
                'contractor_reference',
                'notes',
                'deleted_at',
            ]);
        });

        Schema::table('organization_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'project_reference_prefix',
                'project_reference_sequence',
                'project_completion_mode',
                'project_budget_alert_threshold_percent',
                'project_schedule_alert_threshold_days',
            ]);
        });
    }
};
