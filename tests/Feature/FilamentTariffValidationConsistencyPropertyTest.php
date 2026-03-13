<?php

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Http\Requests\StoreTariffRequest;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Set up Filament panel before each test
beforeEach(function () {
    // Initialize the admin panel to register routes
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// Feature: filament-admin-panel, Property 11: Tariff validation consistency
// Validates: Requirements 5.5
test('Filament TariffResource applies same validation rules as StoreTariffRequest for flat rate tariffs', function () {
    // Create an admin user (only admins can manage tariffs)
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1, // Admin users still need a tenant_id in current schema
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random flat rate tariff data
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
        ],
        'active_from' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'active_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(1, 365))->format('Y-m-d') : null,
    ];
    
    // Property: Validation rules from StoreTariffRequest should match Filament validation
    
    // Test with StoreTariffRequest
    $request = new StoreTariffRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $component->fillForm([
        'provider_id' => $testData['provider_id'],
        'name' => $testData['name'],
        'configuration' => $testData['configuration'],
        'active_from' => $testData['active_from'],
        'active_until' => $testData['active_until'],
    ]);
    
    // Try to create - this will trigger validation
    $component->call('create');

    $filamentErrors = $component->instance()->getErrorBag()->toArray();
    $filamentPasses = empty($filamentErrors);
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses, 
        "Validation outcome mismatch. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
    
    // If both failed, verify they failed for similar reasons
    if (!$formRequestPasses && !$filamentPasses) {
        $formRequestErrorFields = array_keys($formRequestErrors);
        $filamentErrorFields = array_keys($filamentErrors);
        
        // Normalize field names (Filament prefixes with 'data.')
        $normalizedFilamentFields = array_map(function($field) {
            return str_replace('data.', '', $field);
        }, $filamentErrorFields);
        
        // Both should have errors on the same fields
        expect($normalizedFilamentFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($normalizedFilamentFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 11: Tariff validation consistency
// Validates: Requirements 5.5
test('Filament TariffResource applies same validation rules as StoreTariffRequest for time-of-use tariffs', function () {
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1, // Admin users still need a tenant_id in current schema
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random time-of-use tariff data with valid zones
    $zones = [
        [
            'id' => 'day',
            'start' => '07:00',
            'end' => '23:00',
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
        ],
        [
            'id' => 'night',
            'start' => '23:00',
            'end' => '07:00',
            'rate' => fake()->randomFloat(4, 0.01, 0.5),
        ],
    ];
    
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => $zones,
            'weekend_logic' => fake()->randomElement(['apply_night_rate', 'apply_day_rate', 'apply_weekend_rate']),
        ],
        'active_from' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'active_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(1, 365))->format('Y-m-d') : null,
    ];
    
    // Property: Validation rules from StoreTariffRequest should match Filament validation
    
    // Test with StoreTariffRequest
    $request = new StoreTariffRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    // Manually trigger the withValidator callback for time-of-use validation
    $request->withValidator($validator);
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $component->fillForm([
        'provider_id' => $testData['provider_id'],
        'name' => $testData['name'],
        'configuration' => $testData['configuration'],
        'active_from' => $testData['active_from'],
        'active_until' => $testData['active_until'],
    ]);
    
    // Try to create - this will trigger validation
    $component->call('create');

    $filamentErrors = $component->instance()->getErrorBag()->toArray();
    $filamentPasses = empty($filamentErrors);
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
    
    // If both failed, verify they failed for similar reasons
    if (!$formRequestPasses && !$filamentPasses) {
        $formRequestErrorFields = array_keys($formRequestErrors);
        $filamentErrorFields = array_keys($filamentErrors);
        
        // Normalize field names (Filament prefixes with 'data.')
        $normalizedFilamentFields = array_map(function($field) {
            return str_replace('data.', '', $field);
        }, $filamentErrorFields);
        
        // Both should have errors on the same fields
        expect($normalizedFilamentFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($normalizedFilamentFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 11: Tariff validation consistency
// Validates: Requirements 5.5
test('Filament TariffResource rejects invalid flat rate tariffs consistently with StoreTariffRequest', function () {
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1, // Admin users still need a tenant_id in current schema
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate INVALID flat rate tariff data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_provider',
        'invalid_provider',
        'missing_name',
        'empty_name',
        'name_too_long',
        'missing_configuration',
        'missing_type',
        'invalid_type',
        'missing_currency',
        'invalid_currency',
        'missing_rate',
        'negative_rate',
        'non_numeric_rate',
        'missing_active_from',
        'invalid_active_from',
        'active_until_before_active_from',
    ]);
    
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
        ],
        'active_from' => now()->format('Y-m-d'),
        'active_until' => now()->addDays(30)->format('Y-m-d'),
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'missing_provider':
            unset($testData['provider_id']);
            // StoreTariffRequest allows manual tariffs (no provider) unless remote_id is present.
            // Add remote_id to ensure missing provider is invalid consistently in both layers.
            $testData['remote_id'] = 'EXT-' . fake()->numerify('#####');
            break;
        case 'invalid_provider':
            $testData['provider_id'] = 999999;
            break;
        case 'missing_name':
            unset($testData['name']);
            break;
        case 'empty_name':
            $testData['name'] = '';
            break;
        case 'name_too_long':
            $testData['name'] = str_repeat('a', 256); // Max is 255
            break;
        case 'missing_configuration':
            unset($testData['configuration']);
            break;
        case 'missing_type':
            unset($testData['configuration']['type']);
            break;
        case 'invalid_type':
            $testData['configuration']['type'] = 'invalid_type';
            break;
        case 'missing_currency':
            unset($testData['configuration']['currency']);
            break;
        case 'invalid_currency':
            $testData['configuration']['currency'] = 'USD';
            break;
        case 'missing_rate':
            unset($testData['configuration']['rate']);
            break;
        case 'negative_rate':
            $testData['configuration']['rate'] = -1 * fake()->randomFloat(4, 0.01, 1.0);
            break;
        case 'non_numeric_rate':
            $testData['configuration']['rate'] = 'not-a-number';
            break;
        case 'missing_active_from':
            unset($testData['active_from']);
            break;
        case 'invalid_active_from':
            $testData['active_from'] = 'not-a-date';
            break;
        case 'active_until_before_active_from':
            $testData['active_until'] = now()->subDays(30)->format('Y-m-d');
            break;
    }
    
    // Property: Both StoreTariffRequest and Filament should reject invalid data
    
    // Test with StoreTariffRequest
    $request = new StoreTariffRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $formData = [];
    
    if (isset($testData['provider_id'])) {
        $formData['provider_id'] = $testData['provider_id'];
    }
    if (isset($testData['remote_id'])) {
        $formData['remote_id'] = $testData['remote_id'];
    }
    if (isset($testData['name'])) {
        $formData['name'] = $testData['name'];
    }
    if (isset($testData['configuration'])) {
        $formData['configuration'] = $testData['configuration'];
    }
    if (isset($testData['active_from'])) {
        $formData['active_from'] = $testData['active_from'];
    }
    if (isset($testData['active_until'])) {
        $formData['active_until'] = $testData['active_until'];
    }
    
    $component->fillForm($formData);

    // Currency has a default in the Filament form; explicitly clear it when testing "missing_currency".
    if ($invalidationType === 'missing_currency') {
        $component->set('data.configuration.currency', null);
    }
    
    $component->call('create');
    $filamentPasses = $component->instance()->getErrorBag()->isEmpty();
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("StoreTariffRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 11: Tariff validation consistency
// Validates: Requirements 5.5
test('Filament TariffResource rejects invalid time-of-use tariffs consistently with StoreTariffRequest', function () {
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1, // Admin users still need a tenant_id in current schema
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate INVALID time-of-use tariff data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_zones',
        'empty_zones',
        'missing_zone_id',
        'missing_zone_start',
        'invalid_zone_start_format',
        'missing_zone_end',
        'invalid_zone_end_format',
        'missing_zone_rate',
        'negative_zone_rate',
        'invalid_weekend_logic',
    ]);
    
    $zones = [
        [
            'id' => 'day',
            'start' => '07:00',
            'end' => '23:00',
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
        ],
    ];
    
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => $zones,
        ],
        'active_from' => now()->format('Y-m-d'),
        'active_until' => null,
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'missing_zones':
            unset($testData['configuration']['zones']);
            break;
        case 'empty_zones':
            $testData['configuration']['zones'] = [];
            break;
        case 'missing_zone_id':
            unset($testData['configuration']['zones'][0]['id']);
            break;
        case 'missing_zone_start':
            unset($testData['configuration']['zones'][0]['start']);
            break;
        case 'invalid_zone_start_format':
            $testData['configuration']['zones'][0]['start'] = '25:00'; // Invalid hour
            break;
        case 'missing_zone_end':
            unset($testData['configuration']['zones'][0]['end']);
            break;
        case 'invalid_zone_end_format':
            $testData['configuration']['zones'][0]['end'] = '7:00 AM'; // Invalid format
            break;
        case 'missing_zone_rate':
            unset($testData['configuration']['zones'][0]['rate']);
            break;
        case 'negative_zone_rate':
            $testData['configuration']['zones'][0]['rate'] = -0.5;
            break;
        case 'invalid_weekend_logic':
            $testData['configuration']['weekend_logic'] = 'invalid_logic';
            break;
    }
    
    // Property: Both StoreTariffRequest and Filament should reject invalid data
    
    // Test with StoreTariffRequest
    $request = new StoreTariffRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    // Manually trigger the withValidator callback for time-of-use validation
    $request->withValidator($validator);
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $formData = [];
    
    if (isset($testData['provider_id'])) {
        $formData['provider_id'] = $testData['provider_id'];
    }
    if (isset($testData['name'])) {
        $formData['name'] = $testData['name'];
    }
    if (isset($testData['configuration'])) {
        $formData['configuration'] = $testData['configuration'];
    }
    if (isset($testData['active_from'])) {
        $formData['active_from'] = $testData['active_from'];
    }
    if (isset($testData['active_until'])) {
        $formData['active_until'] = $testData['active_until'];
    }
    
    $component->fillForm($formData);
    
    $component->call('create');
    $filamentPasses = $component->instance()->getErrorBag()->isEmpty();
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("StoreTariffRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);
