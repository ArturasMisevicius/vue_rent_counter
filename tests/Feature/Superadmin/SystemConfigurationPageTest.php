<?php

use App\Actions\Superadmin\SystemConfiguration\UpdateSystemSettingAction;
use App\Enums\AuditLogAction;
use App\Enums\SystemSettingCategory;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows grouped system configuration only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::GENERAL,
        'label' => 'Platform Name',
        'key' => 'platform.name',
        'value' => ['value' => 'Tenanto'],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertSuccessful()
        ->assertSeeText('System Configuration')
        ->assertSeeText('General')
        ->assertSeeText('Platform Name');

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertForbidden();
});

it('updates system settings through the action and writes audit history', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $setting = SystemSetting::factory()->create([
        'label' => 'Platform Name',
        'key' => 'platform.name',
        'value' => ['value' => 'Tenanto'],
    ]);

    $updated = app(UpdateSystemSettingAction::class)->handle($setting, [
        'value' => 'Tenanto OS',
    ]);

    $audit = AuditLog::query()
        ->where('subject_type', SystemSetting::class)
        ->where('subject_id', $setting->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($updated->value)->toBe(['value' => 'Tenanto OS'])
        ->and($audit)->not->toBeNull();
});
