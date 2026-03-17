<?php

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Enums\UserStatus;
use App\Filament\Resources\PlatformNotifications\Pages\CreatePlatformNotification;
use App\Filament\Resources\PlatformNotifications\Pages\ListPlatformNotifications;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('only allows superadmins to reach platform notifications control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.platform-notifications.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.platform-notifications.index'))
        ->assertForbidden();
});

it('creates draft notifications and sends them to active platform recipients', function () {
    $superadmin = User::factory()->superadmin()->create();
    $atlas = Organization::factory()->create();
    $birch = Organization::factory()->create();
    $atlasAdmin = User::factory()->admin()->create([
        'organization_id' => $atlas->id,
        'status' => UserStatus::ACTIVE,
    ]);
    $birchTenant = User::factory()->tenant()->create([
        'organization_id' => $birch->id,
        'status' => UserStatus::ACTIVE,
    ]);
    User::factory()->manager()->create([
        'organization_id' => $atlas->id,
        'status' => UserStatus::SUSPENDED,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(CreatePlatformNotification::class)
        ->fillForm([
            'title' => 'Planned maintenance',
            'body' => 'The platform will be read only tonight.',
            'severity' => PlatformNotificationSeverity::WARNING->value,
            'target_scope' => 'all',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $draft = PlatformNotification::query()->firstOrFail();

    expect($draft->status)->toBe(PlatformNotificationStatus::DRAFT)
        ->and($draft->sent_at)->toBeNull()
        ->and($draft->deliveries()->count())->toBe(0);

    Livewire::test(ListPlatformNotifications::class)
        ->assertCanSeeTableRecords([$draft])
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('severity')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('target_scope')
        ->assertTableColumnExists('deliveries_count')
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('severity')
        ->assertTableActionVisible('sendNow', $draft);

    Livewire::test(ListPlatformNotifications::class)
        ->mountTableAction('sendNow', $draft)
        ->assertMountedActionModalSee('Send platform notification')
        ->callMountedTableAction();

    expect($draft->refresh()->status)->toBe(PlatformNotificationStatus::SENT)
        ->and($draft->sent_at)->not->toBeNull()
        ->and($draft->deliveries()->count())->toBe(2)
        ->and(PlatformNotificationDelivery::query()->count())->toBe(2)
        ->and($atlasAdmin->fresh()->notifications()->count())->toBe(1)
        ->and($birchTenant->fresh()->notifications()->count())->toBe(1);
});
