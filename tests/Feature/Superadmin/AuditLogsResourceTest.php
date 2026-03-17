<?php

use App\Enums\AuditLogAction;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists audit logs with filters, colored action states, and before-after metadata', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $updatedLog = AuditLog::factory()->create([
        'actor_id' => $superadmin->id,
        'action' => AuditLogAction::UPDATED,
        'description' => 'System setting updated.',
        'metadata' => [
            'before' => ['value' => 'Tenanto'],
            'after' => ['value' => 'Tenanto Cloud'],
        ],
    ]);
    $cancelledLog = AuditLog::factory()->create([
        'actor_id' => $superadmin->id,
        'action' => AuditLogAction::CANCELLED,
        'description' => 'Subscription cancelled.',
        'metadata' => [
            'before' => ['status' => 'active'],
            'after' => ['status' => 'cancelled'],
        ],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.audit-logs.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.audit-logs.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$updatedLog, $cancelledLog])
        ->assertTableColumnExists('occurred_at')
        ->assertTableColumnExists('action', fn ($column): bool => filled($column->getColor($column->getState())), $updatedLog)
        ->assertTableColumnExists('actor.name')
        ->assertTableColumnExists('description')
        ->assertTableColumnExists('before_state')
        ->assertTableColumnExists('after_state')
        ->assertTableFilterExists('action')
        ->assertTableFilterExists('actor')
        ->assertTableColumnStateSet('before_state', 'value: Tenanto', $updatedLog)
        ->assertTableColumnStateSet('after_state', 'value: Tenanto Cloud', $updatedLog);

    Livewire::test(ListAuditLogs::class)
        ->filterTable('action', AuditLogAction::UPDATED)
        ->assertCanSeeTableRecords([$updatedLog])
        ->assertCanNotSeeTableRecords([$cancelledLog]);
});
