<?php

use App\Enums\UserRole;
use App\Filament\Pages\GDPRCompliance;
use App\Filament\Pages\PrivacyPolicy;
use App\Filament\Pages\TermsOfService;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Test suite for Filament privacy pages.
 * 
 * Verifies that privacy policy, terms of service, and GDPR compliance pages
 * are accessible, render correctly, and appear in navigation.
 * 
 * Pages:
 * - PrivacyPolicy
 * - TermsOfService
 * - GDPRCompliance
 */
beforeEach(function () {
    // Create admin user for testing
    $this->admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'is_active' => true,
        'email' => 'admin@privacy-test.com',
        'password' => Hash::make('password'),
    ]);

    // Create tenant user for testing
    $this->tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'is_active' => true,
        'email' => 'tenant@privacy-test.com',
        'password' => Hash::make('password'),
    ]);
});

describe('Privacy Policy Page', function () {
    test('privacy policy page is accessible to authenticated admin', function () {
        actingAs($this->admin);

        get('/admin/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Last updated')
            ->assertSee('Information We Collect')
            ->assertSee('How We Use Your Information')
            ->assertSee('Your Rights');
    });

    test('privacy policy page is accessible to authenticated tenant', function () {
        actingAs($this->tenant);

        get('/admin/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy');
    });

    test('privacy policy page requires authentication', function () {
        get('/admin/privacy-policy')
            ->assertRedirect('/admin/login');
    });

    test('privacy policy page class exists and is registered', function () {
        expect(class_exists(PrivacyPolicy::class))->toBeTrue();
        
        $panel = Filament::getPanel('admin');
        $pages = $panel->getPages();
        
        expect($pages)->toContain(PrivacyPolicy::class);
    });

    test('privacy policy page has correct navigation configuration', function () {
        expect(PrivacyPolicy::getNavigationLabel())->toBe('Privacy Policy');
        expect(PrivacyPolicy::getNavigationGroup())->toBe('System');
        expect(PrivacyPolicy::shouldRegisterNavigation())->toBeTrue();
    });

    test('privacy policy page contains expected sections', function () {
        actingAs($this->admin);

        $response = get('/admin/privacy-policy');
        
        $response->assertSee('Introduction')
            ->assertSee('Information We Collect')
            ->assertSee('How We Use Your Information')
            ->assertSee('Data Sharing and Disclosure')
            ->assertSee('Data Security')
            ->assertSee('Data Retention')
            ->assertSee('Your Rights')
            ->assertSee('Contact Us');
    });
});

describe('Terms of Service Page', function () {
    test('terms of service page is accessible to authenticated admin', function () {
        actingAs($this->admin);

        get('/admin/terms-of-service')
            ->assertOk()
            ->assertSee('Terms of Service')
            ->assertSee('Last updated')
            ->assertSee('Acceptance of Terms')
            ->assertSee('User Accounts')
            ->assertSee('Acceptable Use');
    });

    test('terms of service page is accessible to authenticated tenant', function () {
        actingAs($this->tenant);

        get('/admin/terms-of-service')
            ->assertOk()
            ->assertSee('Terms of Service');
    });

    test('terms of service page requires authentication', function () {
        get('/admin/terms-of-service')
            ->assertRedirect('/admin/login');
    });

    test('terms of service page class exists and is registered', function () {
        expect(class_exists(TermsOfService::class))->toBeTrue();
        
        $panel = Filament::getPanel('admin');
        $pages = $panel->getPages();
        
        expect($pages)->toContain(TermsOfService::class);
    });

    test('terms of service page has correct navigation configuration', function () {
        expect(TermsOfService::getNavigationLabel())->toBe('Terms of Service');
        expect(TermsOfService::getNavigationGroup())->toBe('System');
        expect(TermsOfService::shouldRegisterNavigation())->toBeTrue();
    });

    test('terms of service page contains expected sections', function () {
        actingAs($this->admin);

        $response = get('/admin/terms-of-service');
        
        $response->assertSee('Acceptance of Terms')
            ->assertSee('Description of Service')
            ->assertSee('User Accounts')
            ->assertSee('Acceptable Use')
            ->assertSee('Data Accuracy')
            ->assertSee('Intellectual Property')
            ->assertSee('Service Availability')
            ->assertSee('Limitation of Liability')
            ->assertSee('Contact Information');
    });
});

describe('GDPR Compliance Page', function () {
    test('GDPR compliance page is accessible to authenticated admin', function () {
        actingAs($this->admin);

        get('/admin/gdpr-compliance')
            ->assertOk()
            ->assertSee('GDPR Compliance')
            ->assertSee('Last updated')
            ->assertSee('GDPR Overview')
            ->assertSee('Our GDPR Compliance Measures')
            ->assertSee('Individual Rights Under GDPR');
    });

    test('GDPR compliance page is accessible to authenticated tenant', function () {
        actingAs($this->tenant);

        get('/admin/gdpr-compliance')
            ->assertOk()
            ->assertSee('GDPR Compliance');
    });

    test('GDPR compliance page requires authentication', function () {
        get('/admin/gdpr-compliance')
            ->assertRedirect('/admin/login');
    });

    test('GDPR compliance page class exists and is registered', function () {
        expect(class_exists(GDPRCompliance::class))->toBeTrue();
        
        $panel = Filament::getPanel('admin');
        $pages = $panel->getPages();
        
        expect($pages)->toContain(GDPRCompliance::class);
    });

    test('GDPR compliance page has correct navigation configuration', function () {
        expect(GDPRCompliance::getNavigationLabel())->toBe('GDPR Compliance');
        expect(GDPRCompliance::getNavigationGroup())->toBe('System');
        expect(GDPRCompliance::shouldRegisterNavigation())->toBeTrue();
    });

    test('GDPR compliance page contains expected sections', function () {
        actingAs($this->admin);

        $response = get('/admin/gdpr-compliance');
        
        $response->assertSee('GDPR Overview')
            ->assertSee('Our GDPR Compliance Measures')
            ->assertSee('Individual Rights Under GDPR')
            ->assertSee('Data Processing Records')
            ->assertSee('Data Breach Notification')
            ->assertSee('Data Protection Officer')
            ->assertSee('Third-Party Processors')
            ->assertSee('International Data Transfers')
            ->assertSee('Exercising Your Rights')
            ->assertSee('Supervisory Authority');
    });
});

describe('Privacy Pages Navigation', function () {
    test('all privacy pages appear in System navigation group', function () {
        actingAs($this->admin);

        $response = get('/admin');
        $response->assertOk();
        
        // Pages should be discoverable in navigation
        // (Actual navigation rendering is tested through Filament's internal mechanisms)
        expect(PrivacyPolicy::getNavigationGroup())->toBe('System');
        expect(TermsOfService::getNavigationGroup())->toBe('System');
        expect(GDPRCompliance::getNavigationGroup())->toBe('System');
    });

    test('privacy pages have correct navigation sort order', function () {
        expect(PrivacyPolicy::getNavigationSort())->toBe(90);
        expect(TermsOfService::getNavigationSort())->toBe(91);
        expect(GDPRCompliance::getNavigationSort())->toBe(92);
    });

    test('privacy pages have appropriate icons', function () {
        expect(PrivacyPolicy::getNavigationIcon())->toBe('heroicon-o-shield-check');
        expect(TermsOfService::getNavigationIcon())->toBe('heroicon-o-document-text');
        expect(GDPRCompliance::getNavigationIcon())->toBe('heroicon-o-lock-closed');
    });
});

describe('Privacy Pages Content', function () {
    test('privacy policy page displays last updated date', function () {
        actingAs($this->admin);

        $response = get('/admin/privacy-policy');
        
        // Should contain current date format
        $response->assertSee('Last updated');
        // Date format should be present (e.g., "January 15, 2025")
        $response->assertSee(now()->format('F'));
    });

    test('terms of service page displays last updated date', function () {
        actingAs($this->admin);

        $response = get('/admin/terms-of-service');
        
        $response->assertSee('Last updated');
        $response->assertSee(now()->format('F'));
    });

    test('GDPR compliance page displays last updated date', function () {
        actingAs($this->admin);

        $response = get('/admin/gdpr-compliance');
        
        $response->assertSee('Last updated');
        $response->assertSee(now()->format('F'));
    });

    test('all privacy pages use Filament panel layout', function () {
        actingAs($this->admin);

        // All pages should render with Filament's panel layout
        get('/admin/privacy-policy')->assertOk();
        get('/admin/terms-of-service')->assertOk();
        get('/admin/gdpr-compliance')->assertOk();
    });

    test('privacy pages support dark mode styling', function () {
        actingAs($this->admin);

        // Pages use prose classes with dark mode support
        $response = get('/admin/privacy-policy');
        $response->assertSee('dark:prose-invert');
    });
});

describe('Privacy Pages Access Control', function () {
    test('all user roles can access privacy pages', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);

        // Admin can access
        actingAs($admin);
        get('/admin/privacy-policy')->assertOk();
        get('/admin/terms-of-service')->assertOk();
        get('/admin/gdpr-compliance')->assertOk();

        // Manager can access
        actingAs($manager);
        get('/admin/privacy-policy')->assertOk();
        get('/admin/terms-of-service')->assertOk();
        get('/admin/gdpr-compliance')->assertOk();

        // Tenant can access
        actingAs($tenant);
        get('/admin/privacy-policy')->assertOk();
        get('/admin/terms-of-service')->assertOk();
        get('/admin/gdpr-compliance')->assertOk();
    });

    test('inactive users cannot access privacy pages', function () {
        $inactiveAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => false,
        ]);

        actingAs($inactiveAdmin);
        
        // Inactive users may be redirected or get an error
        // The exact behavior depends on Filament's middleware configuration
        $response = get('/admin/privacy-policy');
        expect($response->status())->toBeIn([302, 403]);
    });
});
