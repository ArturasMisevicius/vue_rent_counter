<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\BuildingResource\RelationManagers\PropertiesRelationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Property-based tests for PropertiesRelationManager refactoring.
 *
 * Validates the refactoring improvements:
 * - Strict types enforcement
 * - Proper PHPDoc documentation
 * - Type hints on all methods
 * - Config-based defaults
 * - Extracted helper methods
 */
final class PropertiesRelationManagerRefactoringTest extends TestCase
{
    use RefreshDatabase;

    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflection = new ReflectionClass(PropertiesRelationManager::class);
    }

    /**
     * Property 1: File must have strict types declaration.
     */
    public function test_file_has_strict_types_declaration(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        expect($content)
            ->toContain('declare(strict_types=1);')
            ->and(strpos($content, 'declare(strict_types=1);'))
            ->toBeLessThan(100, 'Strict types declaration should be near the top of the file');
    }

    /**
     * Property 2: Class must be final.
     */
    public function test_class_is_final(): void
    {
        expect($this->reflection->isFinal())->toBeTrue();
    }

    /**
     * Property 3: All public methods must have return type hints.
     */
    public function test_all_public_methods_have_return_types(): void
    {
        $publicMethods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($publicMethods as $method) {
            // Skip inherited methods
            if ($method->getDeclaringClass()->getName() !== PropertiesRelationManager::class) {
                continue;
            }

            // Skip constructor and magic methods
            if ($method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }

            expect($method->hasReturnType())
                ->toBeTrue("Method {$method->getName()} must have a return type");
        }
    }

    /**
     * Property 4: All protected methods must have return type hints.
     */
    public function test_all_protected_methods_have_return_types(): void
    {
        $protectedMethods = $this->reflection->getMethods(ReflectionMethod::IS_PROTECTED);

        foreach ($protectedMethods as $method) {
            // Skip inherited methods
            if ($method->getDeclaringClass()->getName() !== PropertiesRelationManager::class) {
                continue;
            }

            expect($method->hasReturnType())
                ->toBeTrue("Method {$method->getName()} must have a return type");
        }
    }

    /**
     * Property 5: All public and protected methods must have PHPDoc blocks.
     */
    public function test_all_methods_have_phpdoc(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            // Skip inherited methods
            if ($method->getDeclaringClass()->getName() !== PropertiesRelationManager::class) {
                continue;
            }

            $docComment = $method->getDocComment();

            expect($docComment)
                ->not->toBeFalse("Method {$method->getName()} must have a PHPDoc block");
        }
    }

    /**
     * Property 6: Helper methods must be extracted for form fields.
     */
    public function test_has_extracted_form_field_helpers(): void
    {
        expect($this->reflection->hasMethod('getAddressField'))->toBeTrue();
        expect($this->reflection->hasMethod('getTypeField'))->toBeTrue();
        expect($this->reflection->hasMethod('getAreaField'))->toBeTrue();
    }

    /**
     * Property 7: Business logic must be extracted from inline actions.
     */
    public function test_has_extracted_action_handlers(): void
    {
        expect($this->reflection->hasMethod('preparePropertyData'))->toBeTrue();
        expect($this->reflection->hasMethod('getTenantManagementForm'))->toBeTrue();
        expect($this->reflection->hasMethod('handleTenantManagement'))->toBeTrue();
        expect($this->reflection->hasMethod('handleExport'))->toBeTrue();
    }

    /**
     * Property 8: Config must be used for default values.
     */
    public function test_uses_config_for_defaults(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        // Should not contain magic numbers
        expect($content)
            ->not->toContain('50); // Default apartment size')
            ->not->toContain('120); // Default house size')
            ->and($content)
            ->toContain("config('billing.property")
            ->toContain('default_apartment_area')
            ->toContain('default_house_area');
    }

    /**
     * Property 9: Validation messages must reference FormRequest.
     */
    public function test_validation_uses_form_request_messages(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        expect($content)
            ->toContain('new StorePropertyRequest()')
            ->toContain('$request->messages()');
    }

    /**
     * Property 10: Table must configure eager loading.
     */
    public function test_table_configures_eager_loading(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        expect($content)
            ->toContain('modifyQueryUsing')
            ->toContain("->with(['tenants', 'meters'])");
    }

    /**
     * Property 11: Type hints must be specific, not generic.
     */
    public function test_uses_specific_type_hints(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        // Should use Property type hint instead of generic $record
        expect($content)
            ->toContain('Property $record')
            ->toContain('?Property $record');
    }

    /**
     * Property 12: Notification class must be imported and used consistently.
     */
    public function test_uses_notification_class_consistently(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        expect($content)
            ->toContain('use Filament\Notifications\Notification;')
            ->toContain('Notification::make()')
            ->not->toContain('\Filament\Notifications\Notification::make()');
    }

    /**
     * Property 13: Config values must be validated.
     */
    public function test_config_has_property_defaults(): void
    {
        $config = config('billing.property');

        expect($config)
            ->toBeArray()
            ->toHaveKeys(['default_apartment_area', 'default_house_area', 'min_area', 'max_area'])
            ->and($config['default_apartment_area'])->toBeNumeric()
            ->and($config['default_house_area'])->toBeNumeric()
            ->and($config['min_area'])->toBe(0)
            ->and($config['max_area'])->toBe(10000);
    }

    /**
     * Property 14: Extracted methods must have proper parameter types.
     */
    public function test_extracted_methods_have_typed_parameters(): void
    {
        $method = $this->reflection->getMethod('setDefaultArea');
        $parameters = $method->getParameters();

        expect($parameters[0]->hasType())->toBeTrue();
        expect($parameters[0]->getType()->getName())->toBe('string');
    }

    /**
     * Property 15: No duplication of validation rules.
     */
    public function test_no_hardcoded_validation_rules(): void
    {
        $filename = $this->reflection->getFileName();
        $content = file_get_contents($filename);

        // Should not have inline validation messages, should reference FormRequest
        $inlineValidationCount = substr_count($content, "'required' => 'The property");

        // Should have minimal inline validation (only in helper methods that reference FormRequest)
        expect($inlineValidationCount)->toBeLessThan(5);
    }
}
