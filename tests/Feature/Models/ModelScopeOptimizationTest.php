<?php

use App\Enums\PlatformNotificationStatus;
use App\Models\AuditLog;
use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\PlatformNotificationRecipient;
use App\Models\PlatformOrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads tenant workspace users with current property summaries eagerly loaded', function (): void {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $firstProperty = Property::factory()->for($organization)->for($building)->create();
    $secondProperty = Property::factory()->for($organization)->for($building)->create();

    $zed = User::factory()->tenant()->for($organization)->create(['name' => 'Zed Tenant']);
    $ada = User::factory()->tenant()->for($organization)->create(['name' => 'Ada Tenant']);
    User::factory()->admin()->for($organization)->create(['name' => 'Admin User']);

    PropertyAssignment::factory()->for($organization)->for($firstProperty)->for($zed, 'tenant')->create([
        'assigned_at' => now()->subDays(3),
    ]);

    PropertyAssignment::factory()->for($organization)->for($secondProperty)->for($ada, 'tenant')->create([
        'assigned_at' => now()->subDay(),
    ]);

    $users = User::query()
        ->withTenantWorkspaceSummary($organization->id)
        ->get();

    expect($users->pluck('name')->all())->toBe(['Ada Tenant', 'Zed Tenant'])
        ->and($users)->toHaveCount(2)
        ->and($users->every(fn (User $user): bool => $user->relationLoaded('currentPropertyAssignment')))->toBeTrue()
        ->and($users->every(fn (User $user): bool => $user->currentPropertyAssignment?->relationLoaded('property') ?? false))->toBeTrue();
});

it('loads organization billing records with related summaries and latest billing first ordering', function (): void {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->for($organization)->create();
    $utilityService = UtilityService::factory()->for($organization)->create();
    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $startReading = MeterReading::factory()->for($organization)->for($property)->for($meter)->for($tenant, 'submittedBy')->create([
        'reading_date' => now()->subMonth()->startOfMonth()->toDateString(),
    ]);
    $endReading = MeterReading::factory()->for($organization)->for($property)->for($meter)->for($tenant, 'submittedBy')->create([
        'reading_date' => now()->startOfMonth()->toDateString(),
    ]);

    BillingRecord::factory()->for($organization)->for($property)->for($utilityService)->for($invoice)->for($tenant, 'tenant')->create([
        'billing_period_start' => now()->subMonth()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->subMonth()->endOfMonth()->toDateString(),
    ]);

    $latestRecord = BillingRecord::factory()->for($organization)->for($property)->for($utilityService)->for($invoice)->for($tenant, 'tenant')->create([
        'meter_reading_start' => $startReading->id,
        'meter_reading_end' => $endReading->id,
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $record = BillingRecord::query()
        ->forOrganizationWorkspace($organization->id)
        ->firstOrFail();

    expect($record->is($latestRecord))->toBeTrue()
        ->and($record->relationLoaded('property'))->toBeTrue()
        ->and($record->relationLoaded('utilityService'))->toBeTrue()
        ->and($record->relationLoaded('invoice'))->toBeTrue()
        ->and($record->relationLoaded('tenant'))->toBeTrue()
        ->and($record->relationLoaded('startReading'))->toBeTrue()
        ->and($record->relationLoaded('endReading'))->toBeTrue();
});

it('exposes acceptance portal invitations through pending translated summary scopes', function (): void {
    $organization = Organization::factory()->create(['name' => 'North House']);
    $inviter = User::factory()->admin()->for($organization)->create(['name' => 'Alice Inviter']);

    OrganizationInvitation::factory()->for($organization)->for($inviter, 'inviter')->create([
        'email' => 'expired@example.test',
        'expires_at' => now()->subHour(),
    ]);

    $activeInvitation = OrganizationInvitation::factory()->for($organization)->for($inviter, 'inviter')->create([
        'email' => 'active@example.test',
        'expires_at' => now()->addDay(),
    ]);

    $loadedInvitation = OrganizationInvitation::query()
        ->forAcceptancePortal()
        ->pending()
        ->forToken($activeInvitation->token)
        ->firstOrFail();

    expect($loadedInvitation->is($activeInvitation))->toBeTrue()
        ->and($loadedInvitation->relationLoaded('organization'))->toBeTrue()
        ->and($loadedInvitation->relationLoaded('inviter'))->toBeTrue();
});

it('loads platform organization invitations for the control plane with inviter summaries', function (): void {
    $inviter = User::factory()->superadmin()->create();

    PlatformOrganizationInvitation::factory()->for($inviter, 'inviter')->create([
        'admin_email' => 'accepted@example.test',
        'accepted_at' => now(),
        'status' => 'accepted',
    ]);

    $pendingInvitation = PlatformOrganizationInvitation::factory()->for($inviter, 'inviter')->create([
        'admin_email' => 'pending@example.test',
    ]);

    $loadedInvitation = PlatformOrganizationInvitation::query()
        ->forControlPlane()
        ->pending()
        ->forEmail('pending@example.test')
        ->firstOrFail();

    expect($loadedInvitation->is($pendingInvitation))->toBeTrue()
        ->and($loadedInvitation->relationLoaded('inviter'))->toBeTrue();
});

it('loads audit feeds with actor and organization summaries', function (): void {
    $organization = Organization::factory()->create();
    $actor = User::factory()->superadmin()->create();
    $subject = Organization::factory()->create();

    $log = AuditLog::factory()->for($organization)->for($actor, 'actor')->create([
        'subject_type' => Organization::class,
        'subject_id' => $subject->id,
    ]);

    $loadedLog = AuditLog::query()
        ->forAuditFeed()
        ->whereKey($log->id)
        ->firstOrFail();

    expect($loadedLog->is($log))->toBeTrue()
        ->and($loadedLog->relationLoaded('actor'))->toBeTrue()
        ->and($loadedLog->relationLoaded('organization'))->toBeTrue()
        ->and(AuditLog::query()->forSubject($subject)->whereKey($log->id)->exists())->toBeTrue()
        ->and($loadedLog->subject?->is($subject))->toBeTrue();
});

it('aggregates platform notification delivery counts and nested user summaries', function (): void {
    $user = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $notification = PlatformNotification::factory()->create([
        'status' => PlatformNotificationStatus::SENT,
        'sent_at' => now(),
    ]);

    PlatformNotificationDelivery::factory()->for($notification, 'notification')->for($user)->create();
    PlatformNotificationRecipient::factory()->for($notification, 'notification')->for($organization)->create([
        'delivery_status' => 'sent',
        'sent_at' => now(),
    ]);

    $loadedNotification = PlatformNotification::query()
        ->sent()
        ->withDeliverySummary()
        ->withDeliveryRelations()
        ->firstOrFail();

    expect($loadedNotification->deliveries_count)->toBe(1)
        ->and($loadedNotification->recipients_count)->toBe(1)
        ->and($loadedNotification->relationLoaded('deliveries'))->toBeTrue()
        ->and($loadedNotification->deliveries->first()?->relationLoaded('user'))->toBeTrue();
});
