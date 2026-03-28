<?php

use App\Enums\AuditLogAction;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationsSummaryAction;
use App\Filament\Actions\Superadmin\Organizations\QueueOrganizationDataExportAction;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Support\Audit\AuditLogger;
use App\Jobs\Superadmin\Organizations\GenerateOrganizationDataExportJob;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationDataExportReadyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('queues a gdpr export for the org owner', function () {
    Queue::fake();

    $superadmin = User::factory()->superadmin()->create();
    [$organization] = seedOwnedOrganizationForExport();

    $this->actingAs($superadmin);

    app(QueueOrganizationDataExportAction::class)->handle($organization, 'Support request');

    Queue::assertPushed(GenerateOrganizationDataExportJob::class, function (GenerateOrganizationDataExportJob $job) use ($organization, $superadmin): bool {
        return $job->organizationId === $organization->id
            && $job->reason === 'Support request'
            && $job->requestedByUserId === $superadmin->id;
    });
});

it('queues support exports from the organization view page action', function () {
    Queue::fake();

    $superadmin = User::factory()->superadmin()->create();
    [$organization] = seedOwnedOrganizationForExport();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('exportData', data: [
            'reason' => 'Owner requested a GDPR export.',
        ]);

    Queue::assertPushed(GenerateOrganizationDataExportJob::class, function (GenerateOrganizationDataExportJob $job) use ($organization, $superadmin): bool {
        return $job->organizationId === $organization->id
            && $job->reason === 'Owner requested a GDPR export.'
            && $job->requestedByUserId === $superadmin->id;
    });
});

it('emails the built gdpr export to the org owner and records audit history', function () {
    Notification::fake();

    $superadmin = User::factory()->superadmin()->create();
    [$organization, $owner] = seedOwnedOrganizationForExport();

    $job = new GenerateOrganizationDataExportJob(
        $organization->id,
        'Support request',
        $superadmin->id,
    );

    $exportPath = null;

    $job->handle(
        app(ExportOrganizationDataAction::class),
        app(AuditLogger::class),
    );

    Notification::assertSentTo($owner, OrganizationDataExportReadyNotification::class, function (OrganizationDataExportReadyNotification $notification, array $channels) use ($organization, &$exportPath): bool {
        $exportPath = $notification->exportPath;

        return $channels === ['mail']
            && $notification->organization->is($organization)
            && $notification->reason === 'Support request'
            && file_exists($notification->exportPath);
    });

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->where('action', AuditLogAction::EXPORTED)
        ->latest('id')
        ->first();

    $activityLog = OrganizationActivityLog::query()
        ->forOrganization($organization->id)
        ->where('action', AuditLogAction::EXPORTED->value)
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($superadmin->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'reason' => 'Support request',
            'delivery' => 'owner_email',
            'owner_user_id' => $owner->id,
            'owner_email' => $owner->email,
        ])
        ->and($activityLog)->not->toBeNull()
        ->and($activityLog?->user_id)->toBe($superadmin->id);

    if (is_string($exportPath) && file_exists($exportPath)) {
        @unlink($exportPath);
    }
});

it('exports the selected organizations with visible summary columns', function () {
    $selectedOrganization = Organization::factory()->create([
        'name' => 'Aurora Estates',
        'slug' => 'aurora-estates',
    ]);

    $selectedOwner = User::factory()->admin()->create([
        'organization_id' => $selectedOrganization->id,
        'email' => 'owner@aurora.test',
    ]);

    $selectedOrganization->forceFill([
        'owner_user_id' => $selectedOwner->id,
    ])->save();

    $selectedSubscription = Subscription::factory()->for($selectedOrganization)->create([
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
    ]);

    SubscriptionPayment::factory()->create([
        'organization_id' => $selectedOrganization->id,
        'subscription_id' => $selectedSubscription->id,
        'duration' => SubscriptionDuration::QUARTERLY,
        'amount' => 150.00,
        'currency' => 'EUR',
        'paid_at' => now()->subDay(),
    ]);

    $selectedBuilding = Building::factory()->create([
        'organization_id' => $selectedOrganization->id,
    ]);

    Property::factory()->create([
        'organization_id' => $selectedOrganization->id,
        'building_id' => $selectedBuilding->id,
    ]);

    User::factory()->count(2)->manager()->create([
        'organization_id' => $selectedOrganization->id,
    ]);

    $secondOrganization = Organization::factory()->create([
        'name' => 'Beacon Holdings',
        'slug' => 'beacon-holdings',
    ]);

    $secondOwner = User::factory()->admin()->create([
        'organization_id' => $secondOrganization->id,
        'email' => 'owner@beacon.test',
    ]);

    $secondOrganization->forceFill([
        'owner_user_id' => $secondOwner->id,
    ])->save();

    $secondSubscription = Subscription::factory()->for($secondOrganization)->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
    ]);

    SubscriptionPayment::factory()->create([
        'organization_id' => $secondOrganization->id,
        'subscription_id' => $secondSubscription->id,
        'duration' => SubscriptionDuration::MONTHLY,
        'amount' => 99.00,
        'currency' => 'EUR',
        'paid_at' => now()->subDays(2),
    ]);

    User::factory()->manager()->create([
        'organization_id' => $secondOrganization->id,
    ]);

    $excludedOrganization = Organization::factory()->create([
        'name' => 'Clearwater Estates',
        'slug' => 'clearwater-estates',
    ]);

    $path = app(ExportOrganizationsSummaryAction::class)->handle(
        collect([$selectedOrganization, $secondOrganization]),
        ['name', 'owner.email', 'status', 'currentSubscription.plan', 'properties_count', 'users_count', 'mrr_display', 'created_at'],
    );

    $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES) ?: []);

    expect($rows[0])->toBe([
        'Name',
        'Owner email',
        'Status',
        'Plan',
        'Properties',
        'Users',
        'MRR',
        'Created',
    ])->and($rows)->toHaveCount(3)
        ->and($rows[1][0])->toBe('Aurora Estates')
        ->and($rows[1][1])->toBe('owner@aurora.test')
        ->and($rows[1][4])->toBe('1')
        ->and($rows[1][5])->toBe('3')
        ->and($rows[1][6])->toBe('EUR 50.00')
        ->and($rows[2][0])->toBe('Beacon Holdings')
        ->and($rows[2][1])->toBe('owner@beacon.test')
        ->and($rows[2][5])->toBe('2')
        ->and(collect($rows)->flatten())->not->toContain($excludedOrganization->name);

    @unlink($path);
});

function seedOwnedOrganizationForExport(): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
        'slug' => 'northwind-towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    return [$organization->fresh(), $owner->fresh()];
}
