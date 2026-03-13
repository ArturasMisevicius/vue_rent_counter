<?php

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;

test('admin tenant create page uses shared design field components', function () {
    $tenantId = 1;

    $admin = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    Property::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.tenants.create'));

    $response->assertOk();
    $response->assertSee(__('tenants.pages.admin_form.title'));
    $response->assertSee(__('tenants.pages.admin_form.subtitle'));
    $response->assertSee('ds-input');
    $response->assertSee('ds-select');
    $response->assertSee('ds-btn');
});
