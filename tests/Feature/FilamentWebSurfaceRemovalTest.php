<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $tenantId = 1001;

    $this->admin = User::factory()->admin($tenantId)->create();

    Subscription::factory()->active()->create([
        'user_id' => $this->admin->id,
    ]);
});

test('legacy filament route names are not registered for browser pages and resources', function (): void {
    expect(Route::has('filament.admin.pages.dashboard'))->toBeFalse();
    expect(Route::has('filament.admin.auth.login'))->toBeFalse();
    expect(Route::has('filament.tenant.pages.dashboard'))->toBeFalse();

    $legacyFilamentNames = collect(app('router')->getRoutes()->getRoutesByName())
        ->keys()
        ->filter(static function (string $name): bool {
            return str_starts_with($name, 'filament.admin.pages.')
                || str_starts_with($name, 'filament.admin.auth.')
                || str_starts_with($name, 'filament.admin.resources.')
                || str_starts_with($name, 'filament.superadmin.resources.')
                || str_starts_with($name, 'filament.tenant.pages.');
        })
        ->values()
        ->all();

    expect($legacyFilamentNames)->toBe([]);
});

test('legacy filament browser urls do not render filament interfaces', function (): void {
    $this->get('/admin/login')->assertNotFound();
    $this->get('/compat/admin/dashboard')->assertNotFound();
    $this->get('/compat/admin/login')->assertNotFound();
    $this->get('/compat/tenant/dashboard')->assertNotFound();
    $this->get('/superadmin/resources-compat/organizations')->assertNotFound();
});

test('removed filament resource aliases are not available in authenticated backoffice sessions', function (): void {
    $this->actingAs($this->admin)
        ->get('/admin/resources/users')
        ->assertNotFound();
});
