<?php

use App\Enums\SystemSettingCategory;
use App\Filament\Pages\SystemConfiguration;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows grouped system settings and allows inline updates', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $generalSetting = SystemSetting::factory()->create([
        'category' => SystemSettingCategory::GENERAL,
        'label' => 'Platform Name',
        'description' => 'Shown in platform-owned interfaces.',
        'type' => 'string',
        'value' => 'Tenanto',
    ]);
    $securitySetting = SystemSetting::factory()->create([
        'category' => SystemSettingCategory::SECURITY,
        'label' => 'Blocked IP Banner Enabled',
        'description' => 'Show support guidance on blocked IP responses.',
        'type' => 'boolean',
        'value' => 'false',
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertOk()
        ->assertSeeText('General')
        ->assertSeeText('Security')
        ->assertSeeText('Platform Name')
        ->assertSeeText('Blocked IP Banner Enabled');

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(SystemConfiguration::class)
        ->set("settingValues.{$generalSetting->id}", 'Tenanto Cloud')
        ->call('updateSetting', $generalSetting->id)
        ->assertHasNoErrors();

    expect($generalSetting->refresh()->value)->toBe('Tenanto Cloud')
        ->and(AuditLog::query()->where('auditable_type', SystemSetting::class)->count())->toBeGreaterThan(0);

    Livewire::test(SystemConfiguration::class)
        ->set("settingValues.{$securitySetting->id}", true)
        ->call('updateSetting', $securitySetting->id)
        ->assertHasNoErrors();

    expect($securitySetting->refresh()->value)->toBe('true');
});
