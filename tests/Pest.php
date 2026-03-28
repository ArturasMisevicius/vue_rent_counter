<?php

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

beforeEach(function (): void {
    auth()->logout();
    Carbon::setTestNow();
    ManagerPermissionService::flushCache();

    config()->set('app.supported_locales', [
        'en' => 'EN',
        'lt' => 'LT',
        'ru' => 'RU',
        'es' => 'ES',
    ]);

    config()->set('tenanto.locales', [
        'en' => 'English',
        'lt' => 'Lietuvių',
        'ru' => 'Русский',
        'es' => 'Español',
    ]);

    app()->setLocale(config('app.locale', 'en'));

    registerSharedTestRoutes();
});

afterEach(function (): void {
    auth()->logout();
    Carbon::setTestNow();
    app()->setLocale(config('app.locale', 'en'));
});

/**
 * @return array{organization: Organization, admin: User, subscription: Subscription}
 */
function createOrgWithAdmin(): array
{
    $organization = Organization::factory()->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $admin->id,
    ])->save();

    $subscription = Subscription::factory()
        ->for($organization)
        ->active()
        ->create();

    return [
        'organization' => $organization->fresh(),
        'admin' => $admin->fresh(),
        'subscription' => $subscription->fresh(),
    ];
}

/**
 * @return array{building: Building, property: Property, tenant: User, assignment: PropertyAssignment}
 */
function createTenantInOrg(User $admin): array
{
    if ($admin->organization_id === null) {
        throw new InvalidArgumentException('The provided admin must belong to an organization.');
    }

    $organization = Organization::query()->findOrFail($admin->organization_id);

    $building = Building::factory()
        ->for($organization)
        ->create();

    $property = Property::factory()
        ->for($organization)
        ->for($building)
        ->create();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    return [
        'building' => $building->fresh(),
        'property' => $property->fresh(),
        'tenant' => $tenant->fresh(),
        'assignment' => $assignment->fresh(),
    ];
}

function signInAs(UserRole $role): User
{
    $user = match ($role) {
        UserRole::SUPERADMIN => User::factory()->superadmin()->create(),
        UserRole::ADMIN => createOrgWithAdmin()['admin'],
        UserRole::MANAGER => (function (): User {
            $workspace = createOrgWithAdmin();

            return User::factory()->manager()->create([
                'organization_id' => $workspace['organization']->id,
            ]);
        })(),
        UserRole::TENANT => (function (): User {
            $workspace = createOrgWithAdmin();

            return createTenantInOrg($workspace['admin'])['tenant'];
        })(),
    };

    test()->actingAs($user);

    return $user->fresh();
}

function registerSharedTestRoutes(): void
{
    if (! Route::has('test.intended')) {
        Route::middleware(['web', 'auth'])
            ->get('/__test/intended', fn () => 'intended')
            ->name('test.intended');
    }

    if (! Route::has('test.session-timeout.web')) {
        Route::middleware(['web', 'auth'])
            ->get('/__test/session-timeout', fn () => 'session timeout')
            ->name('test.session-timeout.web');
    }

    if (! Route::has('test.security.secure-page')) {
        Route::middleware(['web', 'auth'])
            ->get('/__test/security/secure-page', fn () => 'secure')
            ->name('test.security.secure-page');
    }

    if (! Route::has('test.errors.forbidden')) {
        Route::middleware('web')
            ->get('/__test/errors/forbidden', fn () => abort(403))
            ->name('test.errors.forbidden');
    }

    if (! Route::has('test.errors.server')) {
        Route::middleware('web')
            ->get('/__test/errors/server', function (): never {
                abort(500);
            })
            ->name('test.errors.server');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}
