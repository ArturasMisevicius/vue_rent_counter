<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('move_out_processes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_assignment_id')->constrained('property_assignments')->cascadeOnDelete();
            $table->string('status')->index();
            $table->date('move_out_date');
            $table->boolean('final_readings_required')->default(true);
            $table->timestamp('final_readings_completed_at')->nullable();
            $table->foreignId('final_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('rental_contracts')->nullOnDelete();
            $table->string('portal_access_after_move_out')->default('keep_historical_access');
            $table->text('reason')->nullable();
            $table->text('internal_note')->nullable();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'tenant_id', 'status']);
            $table->index(['organization_id', 'property_id', 'status']);
            $table->index(['organization_id', 'move_out_date', 'status']);
        });

        Schema::table('property_assignments', function (Blueprint $table): void {
            $table->date('move_out_date')->nullable()->after('unassigned_at');
            $table->date('billing_start_date')->nullable()->after('move_out_date');
            $table->date('billing_end_date')->nullable()->after('billing_start_date');
            $table->text('move_out_reason')->nullable()->after('billing_end_date');
            $table->foreignId('move_out_scheduled_by_user_id')
                ->nullable()
                ->after('move_out_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('move_out_completed_by_user_id')
                ->nullable()
                ->after('move_out_scheduled_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('move_out_completed_at')->nullable()->after('move_out_completed_by_user_id');

            $table->index(['organization_id', 'property_id', 'move_out_date'], 'property_assignments_org_property_move_out_idx');
            $table->index(['organization_id', 'tenant_user_id', 'move_out_date'], 'property_assignments_org_tenant_move_out_idx');
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->string('reading_type')->default('regular')->after('submission_method')->index();
            $table->foreignId('property_assignment_id')
                ->nullable()
                ->after('reading_type')
                ->constrained('property_assignments')
                ->nullOnDelete();
            $table->foreignId('move_out_process_id')
                ->nullable()
                ->after('property_assignment_id')
                ->constrained('move_out_processes')
                ->nullOnDelete();
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('move_out_process_id')
                ->constrained('invoices')
                ->nullOnDelete();

            $table->index(['organization_id', 'property_id', 'reading_type'], 'meter_readings_org_property_type_idx');
            $table->index(['move_out_process_id', 'reading_type'], 'meter_readings_move_out_type_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('property_assignment_id')
                ->nullable()
                ->after('tenant_user_id')
                ->constrained('property_assignments')
                ->nullOnDelete();
            $table->foreignId('move_out_process_id')
                ->nullable()
                ->after('property_assignment_id')
                ->constrained('move_out_processes')
                ->nullOnDelete();
            $table->string('invoice_type')->default('regular')->after('move_out_process_id')->index();
            $table->boolean('is_final')->default(false)->after('invoice_type')->index();

            $table->index(['organization_id', 'property_assignment_id'], 'invoices_org_assignment_idx');
            $table->index(['organization_id', 'move_out_process_id'], 'invoices_org_move_out_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_org_move_out_idx');
            $table->dropIndex('invoices_org_assignment_idx');
            $table->dropConstrainedForeignId('move_out_process_id');
            $table->dropConstrainedForeignId('property_assignment_id');
            $table->dropColumn(['invoice_type', 'is_final']);
        });

        Schema::table('meter_readings', function (Blueprint $table): void {
            $table->dropIndex('meter_readings_move_out_type_idx');
            $table->dropIndex('meter_readings_org_property_type_idx');
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropConstrainedForeignId('move_out_process_id');
            $table->dropConstrainedForeignId('property_assignment_id');
            $table->dropColumn('reading_type');
        });

        Schema::table('property_assignments', function (Blueprint $table): void {
            $table->dropIndex('property_assignments_org_tenant_move_out_idx');
            $table->dropIndex('property_assignments_org_property_move_out_idx');
            $table->dropConstrainedForeignId('move_out_completed_by_user_id');
            $table->dropConstrainedForeignId('move_out_scheduled_by_user_id');
            $table->dropColumn([
                'move_out_date',
                'billing_start_date',
                'billing_end_date',
                'move_out_reason',
                'move_out_completed_at',
            ]);
        });

        Schema::dropIfExists('move_out_processes');
    }
};
