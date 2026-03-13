<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_impersonation_routes_exist()
    {
        // Test that the routes are registered
        $this->assertTrue(
            collect(\Illuminate\Support\Facades\Route::getRoutes())
                ->contains(fn($route) => $route->getName() === 'superadmin.impersonation.start')
        );

        $this->assertTrue(
            collect(\Illuminate\Support\Facades\Route::getRoutes())
                ->contains(fn($route) => $route->getName() === 'superadmin.impersonation.end')
        );

        $this->assertTrue(
            collect(\Illuminate\Support\Facades\Route::getRoutes())
                ->contains(fn($route) => $route->getName() === 'superadmin.impersonation.history')
        );
    }

    public function test_impersonation_history_requires_superadmin_role()
    {
        // Create a regular user
        $user = User::factory()->create(['role' => 'admin']);

        // Attempt to access impersonation history
        $response = $this->actingAs($user)->get(route('superadmin.impersonation.history'));

        // Should be redirected or forbidden (depending on middleware implementation)
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
    }
}
