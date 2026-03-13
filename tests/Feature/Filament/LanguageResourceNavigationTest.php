<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for LanguageResource navigation and namespace consolidation.
 *
 * Verifies that:
 * - LanguageResource is accessible to superadmins
 * - Navigation works correctly with consolidated namespaces
 * - Table actions use proper Tables\Actions\ namespace
 * - All CRUD operations function correctly
 *
 * @see \App\Filament\Resources\LanguageResource
 */
class LanguageResourceNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that superadmin can access the languages index page.
     *
     * @test
     */
    public function superadmin_can_navigate_to_languages_index(): void
    {
        // Arrange: Create a superadmin user
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        // Create some test languages
        Language::factory()->count(3)->create();

        // Act: Navigate to the languages index page
        $response = $this->actingAs($superadmin)
            ->get(LanguageResource::getUrl('index'));

        // Assert: Page loads successfully
        $response->assertSuccessful();
        $response->assertSee(__('locales.navigation'));
    }

    /**
     * Test that admin cannot access the languages index page.
     *
     * @test
     */
    public function admin_cannot_navigate_to_languages_index(): void
    {
        // Arrange: Create an admin user
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        // Act: Attempt to navigate
        $response = $this->actingAs($admin)
            ->get(LanguageResource::getUrl('index'));

        // Assert: Filament may redirect unauthorized users or return 403
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Expected 403 Forbidden or redirect for unauthorized admin access'
        );
    }

    /**
     * Test that manager cannot access the languages index page.
     *
     * @test
     */
    public function manager_cannot_navigate_to_languages_index(): void
    {
        // Arrange: Create a manager user
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
        ]);

        // Act & Assert: Attempt to navigate should be forbidden
        $response = $this->actingAs($manager)
            ->get(LanguageResource::getUrl('index'));

        $response->assertForbidden();
    }

    /**
     * Test that tenant cannot access the languages index page.
     *
     * @test
     */
    public function tenant_cannot_navigate_to_languages_index(): void
    {
        // Arrange: Create a tenant user
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
        ]);

        // Act & Assert: Attempt to navigate should be forbidden
        $response = $this->actingAs($tenant)
            ->get(LanguageResource::getUrl('index'));

        $response->assertForbidden();
    }

    /**
     * Test that LanguageResource uses consolidated namespace.
     *
     * @test
     */
    public function language_resource_uses_consolidated_namespace(): void
    {
        // Arrange: Get the resource file content
        $reflection = new \ReflectionClass(LanguageResource::class);
        $resourceFile = $reflection->getFileName();
        $resourceContent = file_get_contents($resourceFile);

        // Assert: Uses consolidated namespace
        $this->assertStringContainsString('use Filament\Tables;', $resourceContent);

        // Assert: Uses Tables\Actions\ prefix
        $this->assertStringContainsString('Tables\Actions\EditAction', $resourceContent);
        $this->assertStringContainsString('Tables\Actions\DeleteAction', $resourceContent);

        // Assert: Does NOT use individual imports
        $this->assertStringNotContainsString('use Filament\Tables\Actions\EditAction;', $resourceContent);
        $this->assertStringNotContainsString('use Filament\Tables\Actions\DeleteAction;', $resourceContent);
    }

    /**
     * Test that navigation is only visible to superadmins.
     *
     * @test
     */
    public function navigation_only_visible_to_superadmin(): void
    {
        // Test superadmin
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->actingAs($superadmin);
        $this->assertTrue(LanguageResource::shouldRegisterNavigation());

        // Test admin
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);
        $this->assertFalse(LanguageResource::shouldRegisterNavigation());

        // Test manager
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->actingAs($manager);
        $this->assertFalse(LanguageResource::shouldRegisterNavigation());

        // Test tenant
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->actingAs($tenant);
        $this->assertFalse(LanguageResource::shouldRegisterNavigation());
    }

    /**
     * Test that superadmin can access the create page.
     *
     * @test
     */
    public function superadmin_can_navigate_to_create_language(): void
    {
        // Arrange: Create a superadmin user
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        // Act: Navigate to the create page
        $response = $this->actingAs($superadmin)
            ->get(LanguageResource::getUrl('create'));

        // Assert: Page loads successfully
        $response->assertSuccessful();
    }

    /**
     * Test that superadmin can access the edit page.
     *
     * @test
     */
    public function superadmin_can_navigate_to_edit_language(): void
    {
        // Arrange: Create a superadmin user and a language
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);
        $language = Language::factory()->create();

        // Act: Navigate to the edit page
        $response = $this->actingAs($superadmin)
            ->get(LanguageResource::getUrl('edit', ['record' => $language]));

        // Assert: Page loads successfully
        $response->assertSuccessful();
    }
}
