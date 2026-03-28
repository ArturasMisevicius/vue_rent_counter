<?php

use App\Enums\AuditLogAction;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the audit logs list page as a read-only superadmin surface', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    AuditLog::query()->delete();

    $auditLog = AuditLog::factory()->create([
        'organization_id' => $organization->id,
        'actor_user_id' => $superadmin->id,
        'action' => AuditLogAction::APPROVED,
        'subject_type' => Invoice::class,
        'subject_id' => 321,
        'description' => 'Invoice finalized',
        'ip_address' => '203.0.113.10',
        'metadata' => [
            'context' => [
                'mutation' => 'invoice.finalized',
            ],
            'before' => [
                'status' => 'draft',
                'total_amount' => 125,
            ],
            'after' => [
                'status' => 'finalized',
                'total_amount' => 150,
            ],
        ],
        'occurred_at' => now()->setDate(2026, 3, 24)->setTime(14, 35, 0),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.audit-logs.index'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.audit_logs.plural'))
        ->assertDontSeeText('New Audit Log')
        ->assertSeeText(__('superadmin.audit_logs.columns.user'))
        ->assertSeeText(__('superadmin.audit_logs.filters.action_type'))
        ->assertSeeText(__('superadmin.audit_logs.filters.affected_record_type'))
        ->assertSeeText(__('superadmin.audit_logs.filters.date_range'))
        ->assertSeeText(__('superadmin.audit_logs.filters.from'))
        ->assertSeeText(__('superadmin.audit_logs.filters.to'))
        ->assertSeeText(AuditLogAction::CREATED->label())
        ->assertSeeText(AuditLogAction::UPDATED->label())
        ->assertSeeText(AuditLogAction::DELETED->label())
        ->assertSeeText(__('superadmin.audit_logs.actions.finalized'))
        ->assertSeeText(__('superadmin.audit_logs.actions.payment_processed'))
        ->assertSeeText($superadmin->name)
        ->assertSeeText($superadmin->email)
        ->assertSeeText(AuditLog::subjectTypeLabel(Invoice::class))
        ->assertSeeText('321')
        ->assertSeeText('203.0.113.10')
        ->assertSeeText($auditLog->occurred_at->format('F j, Y g:i A'))
        ->assertSeeText(__('superadmin.audit_logs.diff.before'))
        ->assertSeeText(__('superadmin.audit_logs.diff.after'))
        ->assertSeeText('Status')
        ->assertSeeText('draft')
        ->assertSeeText('finalized')
        ->assertSee('audit-log-diff-row--changed', false);

    $this->actingAs($superadmin);

    Livewire::test(ListAuditLogs::class)
        ->assertTableColumnExists('actor_summary', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.audit_logs.columns.user'))
        ->assertTableColumnExists('display_action', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.audit_logs.columns.action'))
        ->assertTableColumnExists('record_type_label', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.audit_logs.columns.record_type'))
        ->assertTableColumnExists('subject_id', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.audit_logs.columns.record_id'))
        ->assertTableColumnExists('ip_address', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.audit_logs.columns.ip_address'))
        ->assertTableColumnExists('occurred_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.audit_logs.columns.timestamp'))
        ->assertTableFilterExists('user', fn (Filter $filter): bool => $filter->getLabel() === __('superadmin.audit_logs.filters.user'))
        ->assertTableFilterExists('action_type', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.audit_logs.filters.action_type'))
        ->assertTableFilterExists('subject_type', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.audit_logs.filters.affected_record_type'))
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.audit_logs.filters.organization'))
        ->assertTableFilterExists('record_id', fn (Filter $filter): bool => $filter->getLabel() === __('superadmin.audit_logs.columns.record_id'))
        ->assertTableFilterExists('occurred_between', fn (Filter $filter): bool => $filter->getLabel() === __('superadmin.audit_logs.filters.date_range'))
        ->assertTableActionDoesNotExist('view', record: $auditLog)
        ->assertTableActionDoesNotExist('edit', record: $auditLog)
        ->assertTableActionDoesNotExist('delete', record: $auditLog)
        ->assertTableColumnStateSet('actor_summary', $superadmin->name, $auditLog)
        ->assertTableColumnStateSet('display_action', __('superadmin.audit_logs.actions.finalized'), $auditLog)
        ->assertTableColumnStateSet('record_type_label', AuditLog::subjectTypeLabel(Invoice::class), $auditLog)
        ->assertTableColumnStateSet('subject_id', 321, $auditLog)
        ->assertTableColumnStateSet('ip_address', '203.0.113.10', $auditLog)
        ->assertTableColumnStateSet('occurred_at', $auditLog->occurred_at->format('F j, Y g:i A'), $auditLog);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.audit-logs.index'))
        ->assertForbidden();
});

it('filters audit logs by user action type record type and date range', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'Aurora Admin',
        'email' => 'aurora@example.com',
    ]);
    $otherActor = User::factory()->superadmin()->create([
        'name' => 'Beacon Admin',
        'email' => 'beacon@example.com',
    ]);

    AuditLog::query()->delete();

    $matchingLog = AuditLog::factory()->create([
        'actor_user_id' => $superadmin->id,
        'action' => AuditLogAction::APPROVED,
        'subject_type' => Invoice::class,
        'subject_id' => 1001,
        'metadata' => [
            'context' => [
                'mutation' => 'invoice.finalized',
            ],
        ],
        'occurred_at' => now()->subDay(),
    ]);

    $userMismatchLog = AuditLog::factory()->create([
        'actor_user_id' => $otherActor->id,
        'action' => AuditLogAction::APPROVED,
        'subject_type' => Invoice::class,
        'subject_id' => 1002,
        'metadata' => [
            'context' => [
                'mutation' => 'invoice.finalized',
            ],
        ],
        'occurred_at' => now()->subDay(),
    ]);

    $actionMismatchLog = AuditLog::factory()->create([
        'actor_user_id' => $superadmin->id,
        'action' => AuditLogAction::UPDATED,
        'subject_type' => Invoice::class,
        'subject_id' => 1003,
        'metadata' => [
            'context' => [
                'mutation' => 'invoice.payment_recorded',
            ],
        ],
        'occurred_at' => now()->subDay(),
    ]);

    $subjectMismatchLog = AuditLog::factory()->create([
        'actor_user_id' => $superadmin->id,
        'action' => AuditLogAction::APPROVED,
        'subject_type' => Organization::class,
        'subject_id' => 1004,
        'metadata' => [
            'context' => [
                'mutation' => 'invoice.finalized',
            ],
        ],
        'occurred_at' => now()->subDay(),
    ]);

    $dateMismatchLog = AuditLog::factory()->create([
        'actor_user_id' => $superadmin->id,
        'action' => AuditLogAction::APPROVED,
        'subject_type' => Invoice::class,
        'subject_id' => 1005,
        'metadata' => [
            'context' => [
                'mutation' => 'invoice.finalized',
            ],
        ],
        'occurred_at' => now()->subMonths(2),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListAuditLogs::class)
        ->filterTable('user', ['query' => 'aurora@example.com'])
        ->assertCanSeeTableRecords([$matchingLog, $actionMismatchLog, $subjectMismatchLog, $dateMismatchLog])
        ->assertCanNotSeeTableRecords([$userMismatchLog])
        ->resetTableFilters()
        ->filterTable('action_type', 'finalized')
        ->assertCanSeeTableRecords([$matchingLog, $userMismatchLog, $subjectMismatchLog, $dateMismatchLog])
        ->assertCanNotSeeTableRecords([$actionMismatchLog])
        ->resetTableFilters()
        ->filterTable('subject_type', Invoice::class)
        ->assertCanSeeTableRecords([$matchingLog, $userMismatchLog, $actionMismatchLog, $dateMismatchLog])
        ->assertCanNotSeeTableRecords([$subjectMismatchLog])
        ->resetTableFilters()
        ->filterTable('occurred_between', [
            'occurred_from' => now()->subDays(7)->toDateString(),
            'occurred_to' => now()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$matchingLog, $userMismatchLog, $actionMismatchLog, $subjectMismatchLog])
        ->assertCanNotSeeTableRecords([$dateMismatchLog]);
});

it('filters audit logs by organization and record id for organization deep links', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $matchingLog = AuditLog::factory()->create([
        'organization_id' => $organization->id,
        'subject_type' => Invoice::class,
        'subject_id' => 321,
    ]);

    $sameOrganizationDifferentRecord = AuditLog::factory()->create([
        'organization_id' => $organization->id,
        'subject_type' => Invoice::class,
        'subject_id' => 654,
    ]);

    $otherOrganizationLog = AuditLog::factory()->create([
        'organization_id' => $otherOrganization->id,
        'subject_type' => Invoice::class,
        'subject_id' => 321,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListAuditLogs::class)
        ->filterTable('organization', $organization->id)
        ->filterTable('record_id', [
            'subject_id' => 321,
        ])
        ->assertCanSeeTableRecords([$matchingLog])
        ->assertCanNotSeeTableRecords([$sameOrganizationDifferentRecord, $otherOrganizationLog]);
});
