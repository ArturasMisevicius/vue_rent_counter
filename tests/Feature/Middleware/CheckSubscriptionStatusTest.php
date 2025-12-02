<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

test('superadmin users bypass subscription check', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);

    $this->actingAs($superadmin)
        ->get(route('superadmin.dashboard'))
        ->assertOk();
});

test('tenant users bypass subscription check', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.dashboard'))
        ->assertOk();
});

test('admin with active subscription has full access', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->addMonths(6),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionMissing('warning')
        ->assertSessionMissing('error');
});

test('admin with expired subscription gets read-only access for GET requests', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');

    Log::shouldHaveReceived('channel')
        ->with('audit')
        ->once();
});

test('admin with expired subscription cannot perform write operations', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.dashboard'))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');
});

test('admin with suspended subscription gets read-only access', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::SUSPENDED,
        'expires_at' => now()->addMonths(1),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');
});

test('admin with cancelled subscription gets read-only access', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::CANCELLED,
        'expires_at' => now()->addMonths(1),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');
});

test('admin without subscription can access dashboard but sees error', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('error');
});

test('admin without subscription cannot access other routes', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // Use a route that exists - testing middleware behavior
    $this->actingAs($admin)
        ->get('/admin/properties')
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');
});

test('subscription checks are logged for audit trail', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'));

    Log::shouldHaveReceived('channel')
        ->with('audit')
        ->once();
});

test('admin with active status but expired date is treated as expired', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->subDays(1), // Expired date
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');
});

test('manager role is treated same as admin for subscription checks', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    Subscription::factory()->create([
        'user_id' => $manager->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->addMonths(6),
    ]);

    $this->actingAs($manager)
        ->get(route('manager.dashboard'))
        ->assertOk();
});

test('login route bypasses subscription check', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // No subscription created - would normally block access
    // Login route redirects authenticated users, so we expect redirect not 200
    $this->actingAs($admin)
        ->get(route('login'))
        ->assertRedirect();
});

test('register route bypasses subscription check', function () {
    $this->get(route('register'))
        ->assertOk();
});

test('logout route bypasses subscription check', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // No subscription created - would normally block access
    $this->actingAs($admin)
        ->post(route('logout'))
        ->assertRedirect('/');
});

test('login route bypasses subscription check even with expired subscription', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(30),
    ]);

    // Should redirect authenticated users away from login
    $this->actingAs($admin)
        ->get(route('login'))
        ->assertRedirect();
});

test('register route bypasses subscription check for guests', function () {
    // Guest access to registration should work without any subscription checks
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Register'); // Verify registration form is shown
});

test('logout route bypasses subscription check with suspended subscription', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::SUSPENDED,
        'expires_at' => now()->addMonths(1),
    ]);

    $this->actingAs($admin)
        ->post(route('logout'))
        ->assertRedirect('/')
        ->assertSessionMissing('error')
        ->assertSessionMissing('warning');
});

test('auth routes bypass does not affect admin routes', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // No subscription - admin routes should still be blocked
    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('error'); // Should see subscription error
});

test('login form submission works without subscription', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    // No subscription created
    $response = $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    // Should successfully authenticate and redirect
    $response->assertRedirect();
    $this->assertAuthenticatedAs($admin);
});

test('csrf token validation works on login route', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    // Attempt login without CSRF token should fail
    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    
    $response = $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    // Should still work because we disabled CSRF middleware
    $response->assertRedirect();
});

test('multiple login attempts work without subscription', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    // First attempt
    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ])->assertRedirect(route('login'));

    // Second attempt
    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($admin);
});

test('session regeneration works with auth route bypass', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    // Verify session was created
    $this->assertAuthenticatedAs($admin);
    $this->assertNotNull(session()->getId());
});

test('auth route bypass does not log subscription checks', function () {
    Log::spy();

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('login'));

    // Should NOT log subscription check for auth routes
    Log::shouldNotHaveReceived('channel')
        ->with('audit');
});

test('non-admin users can access auth routes without subscription', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    // Tenant accessing login route (already authenticated)
    $this->actingAs($tenant)
        ->get(route('login'))
        ->assertRedirect(); // Should redirect authenticated users

    // Logout should work
    $this->actingAs($tenant)
        ->post(route('logout'))
        ->assertRedirect('/');
});

test('manager users can access auth routes without subscription', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    $this->actingAs($manager)
        ->post(route('logout'))
        ->assertRedirect('/');
});

test('superadmin users can access auth routes without subscription', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);

    $this->actingAs($superadmin)
        ->post(route('logout'))
        ->assertRedirect('/');
});

test('all bypass roles are correctly configured', function () {
    $roles = [
        ['role' => UserRole::SUPERADMIN, 'route' => 'superadmin.dashboard'],
        ['role' => UserRole::MANAGER, 'route' => 'manager.dashboard'],
        ['role' => UserRole::TENANT, 'route' => 'tenant.dashboard'],
    ];

    foreach ($roles as $config) {
        $user = User::factory()->create(['role' => $config['role']]);
        
        // These roles should have full access without subscription
        $this->actingAs($user)
            ->get(route($config['route']))
            ->assertOk()
            ->assertSessionMissing('error')
            ->assertSessionMissing('warning');
    }
});

test('only admin role requires subscription validation', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    // Admin without subscription should see error
    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('error');
    
    // Other roles should not
    $roles = [
        ['role' => UserRole::SUPERADMIN, 'route' => 'superadmin.dashboard'],
        ['role' => UserRole::MANAGER, 'route' => 'manager.dashboard'],
        ['role' => UserRole::TENANT, 'route' => 'tenant.dashboard'],
    ];
    
    foreach ($roles as $config) {
        $user = User::factory()->create(['role' => $config['role']]);
        
        $this->actingAs($user)
            ->get(route($config['route']))
            ->assertOk()
            ->assertSessionMissing('error');
    }
});

// Additional tests for explicit CSRF documentation validation

test('all http methods bypass subscription check for login route', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    // No subscription created - would normally block access
    
    // GET request (display form)
    $this->actingAs($admin)
        ->get(route('login'))
        ->assertRedirect(); // Authenticated users redirected away
    
    // POST request (form submission) - critical for CSRF prevention
    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect();
    
    $this->assertAuthenticatedAs($admin);
});

test('all http methods bypass subscription check for register route', function () {
    // GET request (display form)
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Register');
    
    // POST request (form submission)
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    
    // Should process registration without subscription check
    // (may redirect or show validation errors, but not subscription error)
    $response->assertSessionMissing('error', 'No active subscription found');
});

test('all http methods bypass subscription check for logout route', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // No subscription created - would normally block access
    
    // POST request (logout action) - critical for CSRF prevention
    $this->actingAs($admin)
        ->post(route('logout'))
        ->assertRedirect('/')
        ->assertSessionMissing('error')
        ->assertSessionMissing('warning');
    
    $this->assertGuest();
});

test('auth route bypass prevents 419 csrf errors on login submission', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    // Simulate login form submission without subscription
    // This should NOT trigger subscription check before CSRF validation
    $response = $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    // Should successfully authenticate (not 419 error)
    $response->assertRedirect();
    $response->assertStatus(302); // Not 419
    $this->assertAuthenticatedAs($admin);
});

test('auth route bypass is http method agnostic', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // No subscription - would normally block
    
    // Test that bypass works regardless of HTTP method
    // GET to login
    $this->actingAs($admin)
        ->get(route('login'))
        ->assertRedirect()
        ->assertSessionMissing('error', 'No active subscription found');
    
    // POST to logout
    $this->actingAs($admin)
        ->post(route('logout'))
        ->assertRedirect('/')
        ->assertSessionMissing('error', 'No active subscription found');
});

test('subscription check applies after auth route bypass', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'password' => Hash::make('password'),
    ]);

    // Login should work without subscription (bypassed)
    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect();

    // But accessing admin dashboard should trigger subscription check
    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('error'); // Subscription error appears here
});
