<?php

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Filament\Resources\SecurityViolations\Pages\ListSecurityViolations;
use App\Models\BlockedIpAddress;
use App\Models\Organization;
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
        ->assertSeeText(__('superadmin.security_violations.plural'))
        ->assertDontSeeText('Create')
        ->assertDontSeeText('New Security Violation')
        ->assertSeeText(__('superadmin.security_violations.columns.severity'))
        ->assertSeeText(__('superadmin.security_violations.columns.type'))
        ->assertSeeText(__('superadmin.security_violations.columns.ip_address'))
        ->assertSeeText(__('superadmin.security_violations.columns.user'))
        ->assertSeeText(__('superadmin.security_violations.columns.url'))
        ->assertSeeText(__('superadmin.security_violations.columns.user_agent_summary'))
        ->assertSeeText(__('superadmin.security_violations.columns.timestamp'))
        ->assertSeeText(SecurityViolationType::AUTHENTICATION->label())
        ->assertSeeText(SecurityViolationSeverity::CRITICAL->label())
        ->assertSeeText($actor->name)
        ->assertSeeText($actor->email)
        ->assertSeeText('/admin/login')
        ->assertSeeText(__('superadmin.security_violations.presenter.browser_on_platform', [
            'browser' => __('superadmin.security_violations.presenter.browsers.chrome'),
            'platform' => __('superadmin.security_violations.presenter.platforms.windows'),
        ]))
        ->assertSeeText(__('superadmin.security_violations.placeholders.anonymous'))
        ->assertSeeText('/api/auth/login')
        ->assertSeeText(__('superadmin.security_violations.presenter.curl'))
        ->assertSeeText($authenticatedViolation->ip_address)
        ->assertSeeText($anonymousViolation->ip_address)
        ->assertSeeText($authenticatedViolation->occurred_at->format('F j, Y g:i A'))
        ->assertSeeText($anonymousViolation->occurred_at->format('F j, Y g:i A'));

    $this->actingAs($superadmin);

    Livewire::test(ListSecurityViolations::class)
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.type'))
        ->assertTableColumnExists('severity', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.severity'))
        ->assertTableColumnExists('ip_address', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.ip_address'))
        ->assertTableColumnExists('user_summary', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.user'))
        ->assertTableColumnExists('url', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.url'))
        ->assertTableColumnExists('user_agent_summary', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.user_agent_summary'))
        ->assertTableColumnExists('occurred_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.security_violations.columns.timestamp'))
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.security_violations.filters.organization'))
        ->assertTableFilterExists('review_status', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.security_violations.filters.review_status'))
        ->assertTableFilterExists('severity', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.security_violations.filters.severity'))
        ->assertTableFilterExists('type', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.security_violations.filters.type'))
        ->assertTableFilterExists('occurred_between', fn (Filter $filter): bool => $filter->getLabel() === __('superadmin.security_violations.filters.date_range'))
        ->assertTableActionHasLabel('blockIp', __('superadmin.security_violations.actions.block_ip_address'), record: $authenticatedViolation)
        ->assertTableActionHasLabel('review', __('superadmin.security_violations.actions.review'), record: $authenticatedViolation)
        ->assertTableActionExists(
            'blockIp',
            checkActionUsing: fn (Action $action): bool => $action->isHidden() === false
                && $action->isDisabled() === false
                && $action->getModalHeading() === __('superadmin.security_violations.modals.block_ip_heading')
                && $action->getModalSubmitActionLabel() === __('superadmin.security_violations.actions.block_ip')
                && $action->getModalCancelActionLabel() === __('superadmin.security_violations.actions.cancel'),
            record: $authenticatedViolation,
        )
        ->assertTableActionDoesNotExist('view', record: $authenticatedViolation)
        ->assertTableActionDoesNotExist('edit', record: $authenticatedViolation)
        ->assertTableActionDoesNotExist('delete', record: $authenticatedViolation)
        ->assertTableColumnStateSet('type', SecurityViolationType::AUTHENTICATION->label(), $authenticatedViolation)
        ->assertTableColumnStateSet('severity', SecurityViolationSeverity::CRITICAL->label(), $authenticatedViolation)
        ->assertTableColumnStateSet('url', '/admin/login', $authenticatedViolation)
        ->assertTableColumnStateSet('user_agent_summary', __('superadmin.security_violations.presenter.browser_on_platform', [
            'browser' => __('superadmin.security_violations.presenter.browsers.chrome'),
            'platform' => __('superadmin.security_violations.presenter.platforms.windows'),
        ]), $authenticatedViolation)
        ->assertTableColumnStateSet('user_summary', $actor->name, $authenticatedViolation)
        ->assertTableColumnStateSet('user_summary', __('superadmin.security_violations.placeholders.anonymous'), $anonymousViolation);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.security-violations.index'))
        ->assertForbidden();
});

it('filters security violations by severity type and date range', function () {
    $superadmin = User::factory()->superadmin()->create();
    $northwind = Organization::factory()->create(['name' => 'Northwind']);
    $southwind = Organization::factory()->create(['name' => 'Southwind']);

    $matchingViolation = SecurityViolation::factory()->create([
        'organization_id' => $northwind->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subDays(2),
    ]);

    $typeMismatch = SecurityViolation::factory()->create([
        'organization_id' => $northwind->id,
        'type' => SecurityViolationType::RATE_LIMIT,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subDays(2),
    ]);

    $severityMismatch = SecurityViolation::factory()->create([
        'organization_id' => $northwind->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::LOW,
        'occurred_at' => now()->subDays(2),
    ]);

    $dateMismatch = SecurityViolation::factory()->create([
        'organization_id' => $northwind->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::HIGH,
        'occurred_at' => now()->subMonths(2),
    ]);

    $reviewedViolation = SecurityViolation::factory()->create([
        'organization_id' => $southwind->id,
        'type' => SecurityViolationType::AUTHORIZATION,
        'severity' => SecurityViolationSeverity::MEDIUM,
        'occurred_at' => now()->subDay(),
        'metadata' => [
            'review' => [
                'reviewed_at' => now()->subHours(4)->toIso8601String(),
                'reviewed_by_user_id' => $superadmin->id,
                'note' => 'Reviewed by support',
            ],
        ],
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
        ->assertCanNotSeeTableRecords([$dateMismatch])
        ->resetTableFilters()
        ->filterTable('organization', $northwind->getKey())
        ->assertCanSeeTableRecords([$matchingViolation, $typeMismatch, $severityMismatch, $dateMismatch])
        ->assertCanNotSeeTableRecords([$reviewedViolation])
        ->resetTableFilters()
        ->filterTable('review_status', 'reviewed')
        ->assertCanSeeTableRecords([$reviewedViolation])
        ->assertCanNotSeeTableRecords([$matchingViolation, $typeMismatch, $severityMismatch, $dateMismatch])
        ->resetTableFilters()
        ->filterTable('review_status', 'unreviewed')
        ->assertCanSeeTableRecords([$matchingViolation, $typeMismatch, $severityMismatch, $dateMismatch])
        ->assertCanNotSeeTableRecords([$reviewedViolation]);
});

it('marks a security violation reviewed with a support note', function () {
    $superadmin = User::factory()->superadmin()->create();

    $violation = SecurityViolation::factory()->create([
        'metadata' => ['source' => 'test'],
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListSecurityViolations::class)
        ->mountTableAction('review', $violation)
        ->setTableActionData([
            'note' => 'Investigated by support',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $violation->refresh();

    expect(data_get($violation->metadata, 'review.note'))->toBe('Investigated by support')
        ->and(data_get($violation->metadata, 'review.reviewed_by_user_id'))->toBe($superadmin->id)
        ->and(data_get($violation->metadata, 'review.reviewed_at'))->not->toBeNull();
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
        ->toBe(__('superadmin.security_violations.modals.block_ip_description', ['ip' => '203.0.113.99']));

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
