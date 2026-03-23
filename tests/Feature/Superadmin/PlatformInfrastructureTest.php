<?php

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\BlockedIpAddress;
use App\Models\IntegrationHealthCheck;
use App\Models\Language;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

it('registers superadmin-only platform policies', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    foreach ([
        AuditLog::class,
        IntegrationHealthCheck::class,
        Language::class,
        SecurityViolation::class,
        Subscription::class,
        SystemSetting::class,
        User::class,
    ] as $modelClass) {
        expect(Gate::forUser($superadmin)->allows('viewAny', $modelClass))->toBeTrue()
            ->and(Gate::forUser($admin)->allows('viewAny', $modelClass))->toBeFalse();
    }

    $subscription = Subscription::factory()->for($organization)->create();

    expect(Gate::forUser($superadmin)->allows('extend', $subscription))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('upgrade', $subscription))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('suspend', $subscription))->toBeTrue()
        ->and(Gate::forUser($superadmin)->allows('cancel', $subscription))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('extend', $subscription))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('upgrade', $subscription))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('suspend', $subscription))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('cancel', $subscription))->toBeFalse();
});

it('blocks active blocked ip addresses while allowing expired entries through', function () {
    $superadmin = User::factory()->superadmin()->create();

    BlockedIpAddress::factory()->create([
        'ip_address' => '203.0.113.10',
        'blocked_by_user_id' => $superadmin->id,
        'blocked_until' => now()->addHour(),
    ]);

    BlockedIpAddress::factory()->create([
        'ip_address' => '203.0.113.11',
        'blocked_by_user_id' => $superadmin->id,
        'blocked_until' => now()->subHour(),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->get(route('test.security.ping'))
        ->assertForbidden();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
        ->get(route('test.security.ping'))
        ->assertSuccessful()
        ->assertSeeText('pong');
});

it('writes audit logs for observed models and sanitizes user password changes', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    $organization = Organization::factory()->create();
    $subscription = Subscription::factory()->for($organization)->create();
    $systemSetting = SystemSetting::factory()->create();
    $user = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $originalName = $user->name;

    $user->update([
        'name' => 'Updated Manager',
        'password' => 'new-secret-password',
    ]);

    $organizationLog = AuditLog::query()
        ->where('subject_type', Organization::class)
        ->where('subject_id', $organization->id)
        ->where('action', AuditLogAction::CREATED)
        ->first();

    $subscriptionLog = AuditLog::query()
        ->where('subject_type', Subscription::class)
        ->where('subject_id', $subscription->id)
        ->where('action', AuditLogAction::CREATED)
        ->first();

    $systemSettingLog = AuditLog::query()
        ->where('subject_type', SystemSetting::class)
        ->where('subject_id', $systemSetting->id)
        ->where('action', AuditLogAction::CREATED)
        ->first();

    $userUpdateLog = AuditLog::query()
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($organizationLog)->not->toBeNull()
        ->and($organizationLog?->organization_id)->toBe($organization->id)
        ->and($organizationLog?->actor_user_id)->toBe($superadmin->id)
        ->and($subscriptionLog)->not->toBeNull()
        ->and($subscriptionLog?->organization_id)->toBe($organization->id)
        ->and($systemSettingLog)->not->toBeNull()
        ->and($systemSettingLog?->organization_id)->toBeNull()
        ->and($userUpdateLog)->not->toBeNull()
        ->and($userUpdateLog?->actor_user_id)->toBe($superadmin->id)
        ->and($userUpdateLog?->metadata['before'])->toMatchArray([
            'name' => $originalName,
        ])
        ->and($userUpdateLog?->metadata['after'])->toMatchArray([
            'name' => 'Updated Manager',
        ])
        ->and($userUpdateLog?->metadata['before'])->not->toHaveKey('password')
        ->and($userUpdateLog?->metadata['after'])->not->toHaveKey('password');
});
