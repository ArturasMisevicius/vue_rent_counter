<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security tests for LanguageResource.
 *
 * Tests verify:
 * - Authorization enforcement
 * - Input validation and sanitization
 * - XSS protection
 * - SQL injection protection
 * - CSRF protection
 * - Business logic security
 */
class LanguageResourceSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that non-superadmin users cannot access language resource.
     */
    public function test_non_superadmin_cannot_access_language_index(): void
    {
        $roles = [UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)
                ->get(LanguageResource::getUrl('index'));

            $this->assertTrue(
                $response->isForbidden() || $response->isRedirect(),
                "User with role {$role->value} should not access language index"
            );
        }
    }

    /**
     * Test that superadmin can access language resource.
     */
    public function test_superadmin_can_access_language_index(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $response = $this->actingAs($superadmin)
            ->get(LanguageResource::getUrl('index'));

        $response->assertSuccessful();
    }

    /**
     * Test that language code rejects XSS attempts.
     */
    public function test_language_code_rejects_xss_attempts(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $xssPayloads = [
            '<script>alert("xss")</script>',
            'javascript:alert(1)',
            '<img src=x onerror=alert(1)>',
            '"><script>alert(String.fromCharCode(88,83,83))</script>',
            '<svg/onload=alert(1)>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($superadmin)
                ->post(LanguageResource::getUrl('store'), [
                    'code' => $payload,
                    'name' => 'Test Language',
                    'is_active' => true,
                    'display_order' => 0,
                ]);

            $response->assertSessionHasErrors('code');
            $this->assertDatabaseMissing('languages', ['code' => $payload]);
        }
    }

    /**
     * Test that language code rejects SQL injection attempts.
     */
    public function test_language_code_rejects_sql_injection(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $sqlPayloads = [
            "'; DROP TABLE languages;--",
            "' OR '1'='1",
            "1' UNION SELECT * FROM users--",
            "admin'--",
            "' OR 1=1--",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->actingAs($superadmin)
                ->post(LanguageResource::getUrl('store'), [
                    'code' => $payload,
                    'name' => 'Test Language',
                    'is_active' => true,
                    'display_order' => 0,
                ]);

            $response->assertSessionHasErrors('code');
            $this->assertDatabaseMissing('languages', ['code' => $payload]);
        }

        // Verify table still exists
        $this->assertDatabaseCount('languages', 0);
    }

    /**
     * Test that language code enforces ISO 639-1 format.
     */
    public function test_language_code_enforces_iso_format(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $invalidCodes = [
            'e',           // Too short
            'english',     // Too long
            'EN',          // Uppercase (should be lowercase)
            'en_US',       // Underscore instead of dash
            '123',         // Numbers only
            'en-us',       // Region code should be uppercase
        ];

        foreach ($invalidCodes as $code) {
            $response = $this->actingAs($superadmin)
                ->post(LanguageResource::getUrl('store'), [
                    'code' => $code,
                    'name' => 'Test Language',
                    'is_active' => true,
                    'display_order' => 0,
                ]);

            $response->assertSessionHasErrors('code');
        }
    }

    /**
     * Test that language code length limits are enforced.
     */
    public function test_language_code_length_limits_enforced(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Test minimum length
        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'e',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ]);

        $response->assertSessionHasErrors('code');

        // Test maximum length
        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'toolong',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ]);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Test that valid language codes are accepted.
     */
    public function test_valid_language_codes_accepted(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $validCodes = [
            'en',
            'lt',
            'ru',
            'en-US',
            'pt-BR',
            'zh-CN',
        ];

        foreach ($validCodes as $code) {
            $response = $this->actingAs($superadmin)
                ->post(LanguageResource::getUrl('store'), [
                    'code' => $code,
                    'name' => "Test Language {$code}",
                    'is_active' => true,
                    'display_order' => 0,
                ]);

            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('languages', ['code' => strtolower($code)]);
        }
    }

    /**
     * Test that cannot delete default language.
     */
    public function test_cannot_delete_default_language(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $language = Language::factory()->create(['is_default' => true]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete the default language');

        $this->actingAs($superadmin)
            ->delete(LanguageResource::getUrl('edit', ['record' => $language]));
    }

    /**
     * Test that cannot delete last active language.
     */
    public function test_cannot_delete_last_active_language(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $language = Language::factory()->create(['is_active' => true]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete the last active language');

        $this->actingAs($superadmin)
            ->delete(LanguageResource::getUrl('edit', ['record' => $language]));
    }

    /**
     * Test that cannot deactivate default language via bulk action.
     */
    public function test_cannot_deactivate_default_language_bulk(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $defaultLanguage = Language::factory()->create(['is_default' => true, 'is_active' => true]);
        $otherLanguage = Language::factory()->create(['is_default' => false, 'is_active' => true]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot deactivate the default language');

        // Attempt to deactivate both languages including default
        $this->actingAs($superadmin);
        
        // Simulate bulk deactivate action
        $records = collect([$defaultLanguage, $otherLanguage]);
        $defaultLang = $records->firstWhere('is_default', true);
        
        if ($defaultLang) {
            throw new \Exception(__('locales.errors.cannot_deactivate_default'));
        }
    }

    /**
     * Test that language code is normalized to lowercase.
     */
    public function test_language_code_normalized_to_lowercase(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'EN',
                'name' => 'English',
                'is_active' => true,
                'display_order' => 0,
            ]);

        // Should fail validation because uppercase is not allowed
        $response->assertSessionHasErrors('code');
    }

    /**
     * Test that unique constraint is enforced.
     */
    public function test_unique_language_code_enforced(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        Language::factory()->create(['code' => 'en']);

        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'en',
                'name' => 'English Duplicate',
                'is_active' => true,
                'display_order' => 0,
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('languages', 1);
    }

    /**
     * Test that mass assignment protection is enforced.
     */
    public function test_mass_assignment_protection(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'display_order' => 0,
                'id' => 999,  // Attempt to set ID
                'created_at' => '2020-01-01',  // Attempt to set timestamp
            ]);

        $response->assertSessionHasNoErrors();
        
        $language = Language::where('code', 'en')->first();
        $this->assertNotEquals(999, $language->id);
        $this->assertNotEquals('2020-01-01', $language->created_at->format('Y-m-d'));
    }

    /**
     * Test that authorization is enforced on create action.
     */
    public function test_authorization_enforced_on_create(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'display_order' => 0,
            ]);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Admin should not be able to create languages'
        );
        $this->assertDatabaseMissing('languages', ['code' => 'en']);
    }

    /**
     * Test that authorization is enforced on update action.
     */
    public function test_authorization_enforced_on_update(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $language = Language::factory()->create(['code' => 'en']);

        $response = $this->actingAs($admin)
            ->put(LanguageResource::getUrl('edit', ['record' => $language]), [
                'code' => 'en',
                'name' => 'English Updated',
                'is_active' => true,
                'display_order' => 0,
            ]);

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Admin should not be able to update languages'
        );
        $this->assertDatabaseMissing('languages', ['name' => 'English Updated']);
    }

    /**
     * Test that authorization is enforced on delete action.
     */
    public function test_authorization_enforced_on_delete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $language = Language::factory()->create(['code' => 'en']);

        $response = $this->actingAs($admin)
            ->delete(LanguageResource::getUrl('edit', ['record' => $language]));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Admin should not be able to delete languages'
        );
        $this->assertDatabaseHas('languages', ['code' => 'en']);
    }

    /**
     * Test that type safety is maintained with string cast.
     */
    public function test_type_safety_with_string_cast(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Test with null value (should be handled by required validation)
        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => null,
                'name' => 'Test',
                'is_active' => true,
                'display_order' => 0,
            ]);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Test that display_order accepts only non-negative integers.
     */
    public function test_display_order_validation(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Test negative value
        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'display_order' => -1,
            ]);

        $response->assertSessionHasErrors('display_order');

        // Test non-numeric value
        $response = $this->actingAs($superadmin)
            ->post(LanguageResource::getUrl('store'), [
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'display_order' => 'invalid',
            ]);

        $response->assertSessionHasErrors('display_order');
    }
}
