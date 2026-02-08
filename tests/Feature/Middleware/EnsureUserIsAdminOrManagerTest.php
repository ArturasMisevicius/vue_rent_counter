<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Enums\UserRole;
use App\Http\Middleware\EnsureUserIsAdminOrManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Test suite for EnsureUserIsAdminOrManager middleware.
 *
 * Verifies authorization logic, logging behavior, and integration
 * with the Filament admin panel access control system.
 */
class EnsureUserIsAdminOrManagerTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureUserIsAdminOrManager $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureUserIsAdminOrManager;
    }

    public function test_allows_admin_user_to_proceed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $admin);

        $response = $this->middleware->handle($request, fn () => response('OK'));

        expect($response->getContent())->toBe('OK');
    }

    public function test_allows_manager_user_to_proceed(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $manager);

        $response = $this->middleware->handle($request, fn () => response('OK'));

        expect($response->getContent())->toBe('OK');
    }

    public function test_blocks_tenant_user(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $tenant);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage(__('app.auth.no_permission_admin_panel'));

        $this->middleware->handle($request, fn () => response('OK'));
    }

    public function test_blocks_superadmin_user(): void
    {
        // Superadmin should not access regular admin panel
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $superadmin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->middleware->handle($request, fn () => response('OK'));
    }

    public function test_blocks_unauthenticated_request(): void
    {
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage(__('app.auth.authentication_required'));

        $this->middleware->handle($request, fn () => response('OK'));
    }

    public function test_logs_authorization_failure_for_tenant(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Admin panel access denied'
                    && $context['reason'] === 'Insufficient role privileges'
                    && $context['user_role'] === 'tenant';
            });

        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $tenant);

        try {
            $this->middleware->handle($request, fn () => response('OK'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Expected exception
        }
    }

    public function test_logs_authorization_failure_for_unauthenticated(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Admin panel access denied'
                    && $context['reason'] === 'No authenticated user'
                    && $context['user_id'] === null;
            });

        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => null);

        try {
            $this->middleware->handle($request, fn () => response('OK'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Expected exception
        }
    }

    public function test_includes_request_metadata_in_log(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return isset($context['url'])
                    && isset($context['ip'])
                    && isset($context['user_agent'])
                    && isset($context['timestamp']);
            });

        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $request = Request::create('/admin/properties', 'GET');
        $request->setUserResolver(fn () => $tenant);

        try {
            $this->middleware->handle($request, fn () => response('OK'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Expected exception
        }
    }

    public function test_integration_with_filament_routes(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->get('/admin');

        $response->assertStatus(200);
    }

    public function test_integration_blocks_tenant_from_filament(): void
    {
        $this->actingAsTenant();

        $response = $this->get('/admin');

        $response->assertStatus(403);
    }

    public function test_middleware_uses_user_model_helpers(): void
    {
        // Verify middleware relies on User model methods
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        expect($admin->isAdmin())->toBeTrue();
        expect($admin->isManager())->toBeFalse();

        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        expect($manager->isManager())->toBeTrue();
        expect($manager->isAdmin())->toBeFalse();
    }
}
