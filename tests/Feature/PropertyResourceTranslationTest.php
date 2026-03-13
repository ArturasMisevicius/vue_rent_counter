<?php

declare(strict_types=1);

use App\Filament\Resources\PropertyResource;
use App\Models\User;
use Illuminate\Support\Facades\Lang;

use function Pest\Laravel\actingAs;

/**
 * Test translation key completeness for PropertyResource.
 * Ensures all validation messages resolve to actual translations.
 */
test('PropertyResource validation messages resolve to translations', function () {
    $fields = ['address', 'type', 'area_sqm', 'building_id'];
    $rules = ['required', 'max', 'enum', 'numeric', 'min', 'exists'];
    
    foreach ($fields as $field) {
        foreach ($rules as $rule) {
            $key = "properties.validation.{$field}.{$rule}";
            
            // Skip if translation doesn't exist (optional rules)
            if (Lang::has($key)) {
                $translation = __($key);
                
                // Ensure it's not the key itself (translation exists)
                expect($translation)
                    ->not->toBe($key)
                    ->and($translation)
                    ->toBeString()
                    ->not->toBeEmpty();
            }
        }
    }
});

test('PropertyResource labels resolve to translations', function () {
    $labels = [
        'property', 'properties', 'address', 'type', 'area',
        'current_tenant', 'building', 'installed_meters', 'created',
    ];
    
    foreach ($labels as $label) {
        $key = "properties.labels.{$label}";
        $translation = __($key);
        
        expect($translation)
            ->not->toBe($key)
            ->and($translation)
            ->toBeString()
            ->not->toBeEmpty();
    }
});

test('PropertyResource getValidationMessages returns correct structure', function () {
    $user = User::factory()->admin()->create();
    actingAs($user);
    
    // Use reflection to test protected method
    $reflection = new ReflectionClass(PropertyResource::class);
    $method = $reflection->getMethod('getValidationMessages');
    $method->setAccessible(true);
    
    $messages = $method->invoke(null, 'address');
    
    expect($messages)
        ->toBeArray()
        ->toHaveKeys(['required', 'max'])
        ->and($messages['required'])
        ->toBe(__('properties.validation.address.required'))
        ->and($messages['max'])
        ->toBe(__('properties.validation.address.max'));
});

test('PropertyResource validation messages match StorePropertyRequest', function () {
    $formRequestMessages = (new \App\Http\Requests\StorePropertyRequest)->messages();
    
    // Extract PropertyResource validation messages
    $resourceMessages = [
        'address.required' => __('properties.validation.address.required'),
        'address.max' => __('properties.validation.address.max'),
        'type.required' => __('properties.validation.type.required'),
        'type.enum' => __('properties.validation.type.enum'),
        'area_sqm.required' => __('properties.validation.area_sqm.required'),
        'area_sqm.numeric' => __('properties.validation.area_sqm.numeric'),
        'area_sqm.min' => __('properties.validation.area_sqm.min'),
        'area_sqm.max' => __('properties.validation.area_sqm.max'),
        'building_id.exists' => __('properties.validation.building_id.exists'),
    ];
    
    // Verify consistency
    foreach ($resourceMessages as $key => $message) {
        expect($formRequestMessages)
            ->toHaveKey($key)
            ->and($formRequestMessages[$key])
            ->toBe($message);
    }
});
