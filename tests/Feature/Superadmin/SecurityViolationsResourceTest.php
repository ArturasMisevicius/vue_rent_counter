<?php

use App\Actions\Superadmin\Security\BlockIpAddressAction;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('test.security.ping')) {
        Route::middleware('web')
            ->get('/__test/security/ping', fn () => 'pong')
            ->name('test.security.ping');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('shows security violations only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $violation = SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'type' => SecurityViolationType::AUTHENTICATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'summary' => 'Credential stuffing detected',
        'ip_address' => '203.0.113.45',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.security-violations.index'))
        ->assertSuccessful()
        ->assertSeeText('Security Violations')
        ->assertSeeText($violation->summary)
        ->assertSeeText($violation->ip_address);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.security-violations.index'))
        ->assertForbidden();
});

it('blocks offending addresses through the security action', function () {
    $superadmin = User::factory()->superadmin()->create();

    $blockedIp = app(BlockIpAddressAction::class)->handle([
        'ip_address' => '203.0.113.25',
        'reason' => 'Credential stuffing',
        'blocked_by_user_id' => $superadmin->id,
    ]);

    expect($blockedIp->ip_address)->toBe('203.0.113.25');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.25'])
        ->get(route('test.security.ping'))
        ->assertForbidden();
});
