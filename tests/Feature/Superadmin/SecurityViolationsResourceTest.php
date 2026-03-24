<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Filament\Resources\SecurityViolations\Pages\ListSecurityViolations;
use App\Models\BlockedIpAddress;
use App\Models\SecurityViolation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the security violations list with the required read-only contract', function () {
    $superadmin = User::factory()->superadmin()->create();
    $actor = User::factory()->admin()->create([
        'name' => 'Alice Auditor',
        'email' => 'alice@example.test',
    ]);

    $authenticatedViolation = SecurityViolation::factory()->create([
        'user_id' => $actor->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'ip_address' => '203.0.113.45',
        'metadata' => [
            'url' => 'https://app.example.test/admin/login?attempt=1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        ],
        'occurred_at' => now()->setDate(2026, 3, 24)->setTime(14, 35, 0),
    ]);

    $anonymousViolation = SecurityViolation::factory()->create([
        'user_id' => null,
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::LOW,
        'ip_address' => '203.0.113.46',
        'metadata' => [
            'url' => 'https://app.example.test/api/auth/login',
            'user_agent' => 'curl/8.7.1',
        ],
        'occurred_at' => now()->setDate(2026, 3, 23)->setTime(11, 15, 0),
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.security-violations.index'))
        ->assertSuccessful()
        ->assertSeeText('Security Violations')
        ->assertDontSeeText('Create')
        ->assertDontSeeText('New Security Violation')
        ->assertSeeText('Severity')
        ->assertSeeText('Violation Type')
        ->assertSeeText('IP Address')
        ->assertSeeText('User')
        ->assertSeeText('URL')
        ->assertSeeText('User Agent Summary')
        ->assertSeeText('Timestamp')
        ->assertSeeText(SecurityViolationType::AUTHENTICATION->label())
        ->assertSeeText(SecurityViolationSeverity::CRITICAL->label())
        ->assertSeeText($actor->name)
        ->assertSeeText($actor->email)
        ->assertSeeText('/admin/login')
        ->assertSeeText('Chrome on Windows')
        ->assertSeeText('Anonymous')
        ->assertSeeText('/api/auth/login')
        ->assertSeeText('curl')
        ->assertSeeText($authenticatedViolation->ip_address)
        ->assertSeeText($anonymousViolation->ip_address)
        ->assertSeeText($authenticatedViolation->occurred_at->format('F j, Y g:i A'))
        ->assertSeeText($anonymousViolation->occurred_at->format('F j, Y g:i A'));

    $this->actingAs($superadmin);

    Livewire::test(ListSecurityViolations::class)
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === 'Violation Type')
        ->assertTableColumnExists('severity', fn (TextColumn $column): bool => $column->getLabel() === 'Severity')
        ->assertTableColumnExists('ip_address', fn (TextColumn $column): bool => $column->getLabel() === 'IP Address')
        ->assertTableColumnExists('user_summary', fn (TextColumn $column): bool => $column->getLabel() === 'User')
        ->assertTableColumnExists('url', fn (TextColumn $column): bool => $column->getLabel() === 'URL')
        ->assertTableColumnExists('user_agent_summary', fn (TextColumn $column): bool => $column->getLabel() === 'User Agent Summary')
        ->assertTableColumnExists('occurred_at', fn (TextColumn $column): bool => $column->getLabel() === 'Timestamp')
        ->assertTableFilterExists('severity', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Severity')
        ->assertTableFilterExists('type', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Violation Type')
        ->assertTableFilterExists('occurred_between', fn (Filter $filter): bool => $filter->getLabel() === 'Date Range')
        ->assertTableActionHasLabel('blockIp', 'Block IP Address', record: $authenticatedViolation)
        ->assertTableActionExists(
            'blockIp',
            checkActionUsing: fn (Action $action): bool => $action->isHidden() === false
                && $action->isDisabled() === false
                && $action->getModalHeading() === 'Block IP Address'
                && $action->getModalSubmitActionLabel() === 'Block IP'
                && $action->getModalCancelActionLabel() === 'Cancel',
            record: $authenticatedViolation,
        )
        ->assertTableActionDoesNotExist('view', record: $authenticatedViolation)
        ->assertTableActionDoesNotExist('edit', record: $authenticatedViolation)
        ->assertTableActionDoesNotExist('delete', record: $authenticatedViolation)
        ->assertTableColumnStateSet('type', SecurityViolationType::AUTHENTICATION->label(), $authenticatedViolation)
        ->assertTableColumnStateSet('severity', SecurityViolationSeverity::CRITICAL->label(), $authenticatedViolation)
        ->assertTableColumnStateSet('url', '/admin/login', $authenticatedViolation)
        ->assertTableColumnStateSet('user_agent_summary', 'Chrome on Windows', $authenticatedViolation)
        ->assertTableColumnStateSet('user_summary', $actor->name, $authenticatedViolation)
        ->assertTableColumnStateSet('user_summary', 'Anonymous', $anonymousViolation);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.security-violations.index'))
        ->assertForbidden();
});

it('filters security violations by severity type and date range', function () {
    $superadmin = User::factory()->superadmin()->create();

    $matchingViolation = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subDays(2),
    ]);

    $typeMismatch = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subDays(2),
    ]);

    $severityMismatch = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::LOW,
        'occurred_at' => now()->subDays(2),
    ]);

    $dateMismatch = SecurityViolation::factory()->create([
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subMonths(2),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListSecurityViolations::class)
        ->filterTable('severity', SecurityViolationSeverity::HIGH->value)
        ->assertCanSeeTableRecords([$matchingViolation, $typeMismatch, $dateMismatch])
        ->assertCanNotSeeTableRecords([$severityMismatch])
        ->resetTableFilters()
        ->filterTable('type', SecurityViolationType::AUTHENTICATION->value)
        ->assertCanSeeTableRecords([$matchingViolation, $severityMismatch, $dateMismatch])
        ->assertCanNotSeeTableRecords([$typeMismatch])
        ->resetTableFilters()
        ->filterTable('occurred_between', [
            'occurred_from' => now()->subDays(7)->toDateString(),
            'occurred_to' => now()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$matchingViolation, $typeMismatch, $severityMismatch])
        ->assertCanNotSeeTableRecords([$dateMismatch]);
});

it('blocks an ip address from the list action with the expected confirmation copy', function () {
    $superadmin = User::factory()->superadmin()->create();

    $violation = SecurityViolation::factory()->create([
        'ip_address' => '203.0.113.99',
        'type' => SecurityViolationType::SUSPICIOUS_IP,
        'severity' => SecurityViolationSeverity::CRITICAL,
    ]);

    $this->actingAs($superadmin);

    $component = Livewire::test(ListSecurityViolations::class)
        ->mountTableAction('blockIp', $violation);

    expect($component->instance()->getMountedTableAction()->getModalDescription())
        ->toBe('Are you sure you want to block all access from 203.0.113.99? This will prevent any connection from this address.');

    $component
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $block = BlockedIpAddress::query()
        ->where('ip_address', '203.0.113.99')
        ->first();

    expect($block)->not->toBeNull()
        ->and($block?->ip_address)->toBe('203.0.113.99')
        ->and($block?->blocked_by_user_id)->toBe($superadmin->id);
});
