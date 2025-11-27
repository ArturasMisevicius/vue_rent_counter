<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Superadmin Authentication Test Suite
 *
 * Comprehensive test coverage for authentication flows across all user roles
 * (Superadmin, Admin, Manager, Tenant) with focus on the is_active account
 * status check. Validates role-based redirects, account deactivation, session
 * security, and error messaging.
 *
 * @package Tests\Feature
 * @category Authentication
 * @see \App\Http\Controllers\Auth\LoginController
 * @see \App\Models\User
 * @see \App\Enums\UserRole
 *
 * Requirements Coverage:
 * - Requirement 1.1: Superadmin login and dashboard access
 * - Requirement 7.1: Account deactivation prevents login
 * - Requirement 8.1: User authentication and redirect
 * - Requirement 8.4: Deactivated account login prevention with messaging
 * - Requirement 12.1: Login redirect logic for superadmin
 *
 * Test Isolation:
 * Each test uses unique tenant_id values to prevent conflicts:
 * - Deactivated admin: tenant_id = 100
 * - Deactivated tenant: tenant_id = 200
 * - All active roles: tenant_id = 300
 * - Invalid credentials: tenant_id = 400
 * - Session regeneration: tenant_id = 500
 * - Remember me: tenant_id = 600
 *
 * @link docs/testing/SUPERADMIN_AUTHENTICATION_TEST.md Full documentation
 * @link docs/api/AUTHENTICATION_API.md Authentication API reference
 * @link .kiro/specs/3-hierarchical-user-management/ Hierarchical user spec
 */
class SuperadminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that superadmin can login and is redirected to superadmin dashboard.
     *
     * Validates that an active superadmin user can successfully authenticate
     * and is redirected to the superadmin-specific dashboard. This test ensures
     * the role-based redirect logic works correctly for the highest privilege level.
     *
     * @test
     * @group authentication
     * @group superadmin
     * @group hierarchical-user-management
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \App\Http\Middleware\RedirectIfAuthenticated
     *
     * Requirements:
     * - 1.1: Superadmin login and dashboard access
     * - 8.1: User authentication and redirect
     *
     * Test Flow:
     * 1. Create superadmin user with is_active=true, tenant_id=null
     * 2. Submit login credentials via POST /login
     * 3. Assert redirect to /superadmin/dashboard
     * 4. Assert user is authenticated
     *
     * @return void
     */
    public function test_superadmin_can_login_and_redirects_to_superadmin_dashboard(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'tenant_id' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertAuthenticatedAs($superadmin);
    }

    /**
     * Test that deactivated superadmin cannot login.
     *
     * Ensures that a deactivated superadmin account cannot authenticate,
     * even with valid credentials. Validates that the is_active flag is
     * properly enforced and appropriate error messaging is displayed.
     *
     * @test
     * @group authentication
     * @group superadmin
     * @group account-deactivation
     * @group security
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \App\Models\User::isActive
     *
     * Requirements:
     * - 7.1: Account deactivation prevents login
     * - 8.4: Deactivated account login prevention with messaging
     *
     * Test Flow:
     * 1. Create superadmin with is_active=false
     * 2. Attempt login with valid credentials
     * 3. Assert redirect to /login
     * 4. Assert session has validation errors
     * 5. Assert user remains unauthenticated
     * 6. Verify specific deactivation error message
     *
     * Expected Error:
     * "Your account has been deactivated. Please contact your administrator for assistance."
     *
     * @return void
     */
    public function test_deactivated_superadmin_cannot_login(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
            'tenant_id' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        // Verify specific error message about deactivation
        $response->assertSessionHasErrorsIn('default', [
            'email' => 'Your account has been deactivated. Please contact your administrator for assistance.',
        ]);
    }

    /**
     * Test that deactivated admin cannot login.
     *
     * Validates that deactivated admin accounts are prevented from logging in.
     * Uses unique tenant_id to ensure test isolation and prevent conflicts
     * with other tests.
     *
     * @test
     * @group authentication
     * @group admin
     * @group account-deactivation
     * @group security
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \App\Models\User::isActive
     *
     * Requirements:
     * - 7.1: Account deactivation prevents login
     * - 8.4: Deactivated account login prevention with messaging
     *
     * Test Flow:
     * 1. Create admin with is_active=false, tenant_id=100
     * 2. Attempt login with valid credentials
     * 3. Assert authentication failure
     * 4. Verify deactivation error message
     *
     * Implementation Notes:
     * - Uses tenant_id=100 for test isolation
     * - Validates deactivation works for admin-level roles
     *
     * @return void
     */
    public function test_deactivated_admin_cannot_login(): void
    {
        // Create admin with unique tenant_id
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
            'tenant_id' => 100, // Use unique tenant_id to avoid conflicts
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        // Verify specific error message about deactivation
        $response->assertSessionHasErrorsIn('default', [
            'email' => 'Your account has been deactivated. Please contact your administrator for assistance.',
        ]);
    }

    /**
     * Test that deactivated tenant cannot login.
     *
     * Ensures tenant accounts respect the is_active flag and cannot login
     * when deactivated. Creates full tenant hierarchy (building → property → user)
     * to validate that property relationships don't bypass deactivation check.
     *
     * @test
     * @group authentication
     * @group tenant
     * @group account-deactivation
     * @group security
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \App\Models\User::isActive
     *
     * Requirements:
     * - 7.1: Account deactivation prevents login
     * - 8.4: Deactivated account login prevention with messaging
     *
     * Test Flow:
     * 1. Create building with tenant_id=200
     * 2. Create property linked to building
     * 3. Create tenant with is_active=false, linked to property
     * 4. Attempt login with valid credentials
     * 5. Assert authentication failure
     * 6. Verify deactivation error message
     *
     * Implementation Notes:
     * - Creates full hierarchy: building → property → tenant
     * - Uses tenant_id=200 for isolation
     * - Validates property relationships maintained
     *
     * @return void
     */
    public function test_deactivated_tenant_cannot_login(): void
    {
        // Create necessary database records for tenant
        $building = Building::factory()->create(['tenant_id' => 200]);
        $property = Property::factory()->create([
            'building_id' => $building->id,
            'tenant_id' => 200,
        ]);
        
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => 'tenant@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
            'tenant_id' => 200,
            'property_id' => $property->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'tenant@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        // Verify specific error message about deactivation
        $response->assertSessionHasErrorsIn('default', [
            'email' => 'Your account has been deactivated. Please contact your administrator for assistance.',
        ]);
    }

    /**
     * Test that active users of all roles can login successfully.
     *
     * Comprehensive test validating that all user roles (superadmin, admin,
     * manager, tenant) can successfully authenticate when active and are
     * redirected to their role-specific dashboards. Tests sequential logins
     * with proper logout between iterations.
     *
     * @test
     * @group authentication
     * @group all-roles
     * @group role-based-redirect
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \App\Http\Middleware\RedirectIfAuthenticated
     *
     * Requirements:
     * - 1.1: Superadmin login and dashboard access
     * - 8.1: User authentication and redirect
     *
     * Test Flow:
     * 1. Create building and property with tenant_id=300
     * 2. Create users for all four roles with is_active=true
     * 3. For each role:
     *    - Submit login credentials
     *    - Assert redirect to role-specific dashboard
     *    - Assert authentication successful
     *    - Logout to prepare for next iteration
     *
     * Expected Redirects:
     * - Superadmin → /superadmin/dashboard
     * - Admin → /admin/dashboard
     * - Manager → /manager/dashboard
     * - Tenant → /tenant/dashboard
     *
     * Implementation Notes:
     * - Tests all four user roles in single test
     * - Uses shared tenant_id=300 for admin/manager/tenant
     * - Explicitly logs out between iterations for clean state
     *
     * @return void
     */
    public function test_all_active_roles_can_login(): void
    {
        // Create necessary database records for tenant
        $building = Building::factory()->create(['tenant_id' => 300]);
        $property = Property::factory()->create([
            'building_id' => $building->id,
            'tenant_id' => 300,
        ]);
        
        $users = [
            'superadmin' => User::factory()->create([
                'role' => UserRole::SUPERADMIN,
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'tenant_id' => null,
            ]),
            'admin' => User::factory()->create([
                'role' => UserRole::ADMIN,
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'tenant_id' => 300,
            ]),
            'manager' => User::factory()->create([
                'role' => UserRole::MANAGER,
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'tenant_id' => 300,
            ]),
            'tenant' => User::factory()->create([
                'role' => UserRole::TENANT,
                'email' => 'tenant@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
                'tenant_id' => 300,
                'property_id' => $property->id,
            ]),
        ];

        $expectedRedirects = [
            'superadmin' => '/superadmin/dashboard',
            'admin' => '/admin/dashboard',
            'manager' => '/manager/dashboard',
            'tenant' => '/tenant/dashboard',
        ];

        foreach ($users as $role => $user) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect($expectedRedirects[$role]);
            $this->assertAuthenticatedAs($user);
            
            // Logout for next iteration
            $this->post('/logout');
        }
    }

    /**
     * Test that invalid credentials are rejected for active accounts.
     *
     * Validates that incorrect passwords are rejected even for active accounts.
     * Ensures that is_active check doesn't bypass password validation and
     * proper error messaging is displayed for invalid credentials.
     *
     * @test
     * @group authentication
     * @group security
     * @group credential-validation
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \Illuminate\Foundation\Auth\AuthenticatesUsers::attemptLogin
     *
     * Requirements:
     * - 8.4: Invalid credential handling with appropriate messaging
     *
     * Test Flow:
     * 1. Create active admin with password "correct-password"
     * 2. Attempt login with password "wrong-password"
     * 3. Assert authentication failure
     * 4. Verify error message indicates invalid credentials
     *
     * Expected Error:
     * "The provided credentials do not match our records."
     *
     * Security Notes:
     * - Validates is_active doesn't bypass password check
     * - Ensures proper error messaging for invalid credentials
     * - Tests with active account to isolate credential validation
     *
     * @return void
     */
    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'password' => Hash::make('correct-password'),
            'is_active' => true,
            'tenant_id' => 400,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        
        // Verify error message for invalid credentials
        $response->assertSessionHasErrorsIn('default', [
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Test that session is regenerated on successful login.
     *
     * Security test ensuring that session IDs are regenerated upon successful
     * login to prevent session fixation attacks. This is a critical security
     * feature that ensures each authenticated session has a fresh session ID.
     *
     * @test
     * @group authentication
     * @group security
     * @group session-management
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \Illuminate\Foundation\Auth\AuthenticatesUsers::login
     *
     * Requirements:
     * - 8.1: Session security and regeneration
     *
     * Test Flow:
     * 1. Create active admin user
     * 2. Capture current session ID
     * 3. Perform successful login
     * 4. Capture new session ID
     * 5. Assert session IDs are different
     *
     * Security Implications:
     * - Prevents session fixation attacks
     * - Ensures fresh session for authenticated user
     * - Standard Laravel security practice
     *
     * Implementation Notes:
     * - Uses tenant_id=500 for isolation
     * - Validates Laravel's built-in session regeneration
     *
     * @return void
     */
    public function test_session_is_regenerated_on_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'tenant_id' => 500,
        ]);

        $oldSessionId = session()->getId();

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $newSessionId = session()->getId();

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotEquals($oldSessionId, $newSessionId, 'Session ID should be regenerated on login');
    }

    /**
     * Test that remember me functionality works.
     *
     * Validates that the "remember me" feature correctly sets a remember token
     * for persistent authentication across browser sessions. Ensures the token
     * is generated and stored in the database.
     *
     * @test
     * @group authentication
     * @group remember-me
     * @group session-management
     *
     * @covers \App\Http\Controllers\Auth\LoginController::login
     * @covers \Illuminate\Foundation\Auth\AuthenticatesUsers::attemptLogin
     * @covers \App\Models\User::setRememberToken
     *
     * Requirements:
     * - 8.1: Remember me functionality
     *
     * Test Flow:
     * 1. Create active admin user
     * 2. Submit login with remember=true parameter
     * 3. Assert successful authentication and redirect
     * 4. Refresh user model from database
     * 5. Assert remember token is set
     *
     * Expected Behavior:
     * - Successful authentication
     * - Remember token generated and stored
     * - Token is not null after login
     * - Long-lived cookie set (default: 5 years)
     *
     * Implementation Notes:
     * - Tests Laravel's built-in remember me functionality
     * - Validates token persistence in database
     * - Uses tenant_id=600 for isolation
     *
     * @return void
     */
    public function test_remember_me_functionality(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'tenant_id' => 600,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
        
        // Verify remember token is set
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }
}
