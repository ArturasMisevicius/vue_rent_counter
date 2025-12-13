# Service Validation Examples

## Overview

This document provides practical examples of using the ServiceValidationEngine in various scenarios within the Universal Utility Management System.

## Basic Usage Examples

### 1. Simple Meter Reading Validation

```php
use App\Services\ServiceValidationEngine;
use App\Models\MeterReading;

class MeterReadingController extends Controller
{
    public function __construct(
        private readonly ServiceValidationEngine $validator
    ) {}

    public function validateReading(MeterReading $reading)
    {
        // Basic validation
        $result = $this->validator->validateMeterReading($reading);
        
        if (!$result['is_valid']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
                'warnings' => $result['warnings']
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reading validated successfully',
            'warnings' => $result['warnings'],
            'recommendations' => $result['recommendations']
        ]);
    }
}
```

### 2. Validation with Service Configuration Override

```php
public function validateWithCustomConfig(MeterReading $reading, Request $request)
{
    // Load specific service configuration
    $serviceConfig = ServiceConfiguration::findOrFail($request->service_configuration_id);
    
    // Validate with custom configuration
    $result = $this->validator->validateMeterReading($reading, $serviceConfig);
    
    // Handle different validation outcomes
    if (!$result['is_valid']) {
        // Log validation failure for audit
        Log::warning('Meter reading validation failed', [
            'reading_id' => $reading->id,
            'service_config_id' => $serviceConfig->id,
            'errors' => $result['errors']
        ]);
        
        return back()->withErrors($result['errors']);
    }
    
    // Process warnings if any
    if (!empty($result['warnings'])) {
        session()->flash('warnings', $result['warnings']);
    }
    
    return redirect()->route('meter-readings.show', $reading)
        ->with('success', 'Reading validated successfully');
}
```

## Advanced Usage Examples

### 3. Batch Validation with Error Handling

```php
use Illuminate\Support\Collection;

class BatchValidationService
{
    public function __construct(
        private readonly ServiceValidationEngine $validator
    ) {}

    public function validateBatch(Collection $readings): array
    {
        try {
            // Perform batch validation
            $result = $this->validator->batchValidateReadings($readings);
            
            // Process results
            $processedResults = $this->processBatchResults($result);
            
            // Generate summary report
            $summary = $this->generateSummaryReport($result);
            
            return [
                'success' => true,
                'results' => $processedResults,
                'summary' => $summary,
                'performance' => $result['performance_metrics']
            ];
            
        } catch (\InvalidArgumentException $e) {
            Log::error('Batch validation input error', [
                'error' => $e->getMessage(),
                'reading_count' => $readings->count()
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid input: ' . $e->getMessage()
            ];
            
        } catch (\Exception $e) {
            Log::error('Batch validation system error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'System error occurred during validation'
            ];
        }
    }

    private function processBatchResults(array $batchResult): array
    {
        $processed = [
            'valid' => [],
            'invalid' => [],
            'warnings' => []
        ];

        foreach ($batchResult['results'] as $readingId => $result) {
            if ($result['is_valid']) {
                $processed['valid'][] = $readingId;
                
                if (!empty($result['warnings'])) {
                    $processed['warnings'][$readingId] = $result['warnings'];
                }
            } else {
                $processed['invalid'][$readingId] = $result['errors'];
            }
        }

        return $processed;
    }

    private function generateSummaryReport(array $batchResult): array
    {
        return [
            'total_processed' => $batchResult['total_readings'],
            'success_rate' => $batchResult['summary']['validation_rate'],
            'error_rate' => $batchResult['summary']['error_rate'],
            'warnings_per_reading' => $batchResult['summary']['average_warnings_per_reading'],
            'processing_time' => $batchResult['performance_metrics']['duration'],
            'cache_efficiency' => $batchResult['performance_metrics']['cache_hits'] / 
                                 max(1, $batchResult['total_readings'])
        ];
    }
}
```

### 4. Rate Change Validation with Business Logic

```php
class RateChangeService
{
    public function __construct(
        private readonly ServiceValidationEngine $validator
    ) {}

    public function validateRateChange(
        ServiceConfiguration $config, 
        array $newRateSchedule,
        ?Carbon $effectiveDate = null
    ): array {
        // Prepare rate schedule with effective date
        $rateSchedule = array_merge($newRateSchedule, [
            'effective_from' => $effectiveDate?->toDateString() ?? now()->addDays(7)->toDateString()
        ]);

        // Validate rate change restrictions
        $validationResult = $this->validator->validateRateChangeRestrictions(
            $config, 
            $rateSchedule
        );

        if (!$validationResult['is_valid']) {
            return [
                'approved' => false,
                'errors' => $validationResult['errors'],
                'current_rate' => $config->getEffectiveRate(),
                'proposed_rate' => $newRateSchedule['rate_per_unit'] ?? null
            ];
        }

        // Calculate impact analysis
        $impactAnalysis = $this->calculateRateChangeImpact($config, $newRateSchedule);

        return [
            'approved' => true,
            'warnings' => $validationResult['warnings'],
            'recommendations' => $validationResult['recommendations'],
            'impact_analysis' => $impactAnalysis,
            'approval_required' => $impactAnalysis['requires_approval']
        ];
    }

    private function calculateRateChangeImpact(
        ServiceConfiguration $config, 
        array $newRateSchedule
    ): array {
        $currentRate = $config->getEffectiveRate();
        $newRate = $newRateSchedule['rate_per_unit'] ?? $currentRate;
        
        $percentageChange = $currentRate > 0 
            ? (($newRate - $currentRate) / $currentRate) * 100 
            : 0;

        return [
            'current_rate' => $currentRate,
            'new_rate' => $newRate,
            'percentage_change' => round($percentageChange, 2),
            'absolute_change' => $newRate - $currentRate,
            'requires_approval' => abs($percentageChange) > 10, // 10% threshold
            'estimated_monthly_impact' => $this->estimateMonthlyImpact($config, $percentageChange)
        ];
    }

    private function estimateMonthlyImpact(ServiceConfiguration $config, float $percentageChange): array
    {
        // Get average monthly consumption for properties using this configuration
        $avgConsumption = $config->meters()
            ->with(['readings' => function ($query) {
                $query->where('reading_date', '>=', now()->subMonths(3))
                      ->where('validation_status', ValidationStatus::VALIDATED);
            }])
            ->get()
            ->flatMap->readings
            ->avg('value') ?? 0;

        $currentMonthlyCost = $avgConsumption * $config->getEffectiveRate();
        $newMonthlyCost = $currentMonthlyCost * (1 + $percentageChange / 100);

        return [
            'average_consumption' => round($avgConsumption, 2),
            'current_monthly_cost' => round($currentMonthlyCost, 2),
            'new_monthly_cost' => round($newMonthlyCost, 2),
            'monthly_difference' => round($newMonthlyCost - $currentMonthlyCost, 2)
        ];
    }
}
```

## Filament Integration Examples

### 5. Filament Resource with Validation

```php
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use App\Services\ServiceValidationEngine;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('meter_id')
                    ->relationship('meter', 'serial_number')
                    ->required(),
                
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                        // Real-time validation feedback
                        if ($state && $get('meter_id')) {
                            $validator = app(ServiceValidationEngine::class);
                            
                            // Create temporary reading for validation
                            $tempReading = new MeterReading([
                                'meter_id' => $get('meter_id'),
                                'value' => $state,
                                'reading_date' => now(),
                            ]);
                            
                            $result = $validator->validateMeterReading($tempReading);
                            
                            if (!$result['is_valid']) {
                                $set('validation_errors', implode(', ', $result['errors']));
                            } else {
                                $set('validation_errors', null);
                                if (!empty($result['warnings'])) {
                                    $set('validation_warnings', implode(', ', $result['warnings']));
                                }
                            }
                        }
                    }),
                
                Forms\Components\DatePicker::make('reading_date')
                    ->required()
                    ->default(now()),
                
                Forms\Components\Placeholder::make('validation_errors')
                    ->content(fn ($get) => $get('validation_errors'))
                    ->visible(fn ($get) => !empty($get('validation_errors')))
                    ->extraAttributes(['class' => 'text-red-600']),
                
                Forms\Components\Placeholder::make('validation_warnings')
                    ->content(fn ($get) => $get('validation_warnings'))
                    ->visible(fn ($get) => !empty($get('validation_warnings')))
                    ->extraAttributes(['class' => 'text-yellow-600']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('meter.serial_number')
                    ->label('Meter')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Reading')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reading_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('validation_status')
                    ->colors([
                        'success' => ValidationStatus::VALIDATED,
                        'warning' => ValidationStatus::REQUIRES_REVIEW,
                        'danger' => ValidationStatus::REJECTED,
                        'secondary' => ValidationStatus::PENDING,
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('validate')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (MeterReading $record) {
                        $validator = app(ServiceValidationEngine::class);
                        $result = $validator->validateMeterReading($record);
                        
                        if ($result['is_valid']) {
                            $record->update(['validation_status' => ValidationStatus::VALIDATED]);
                            
                            Notification::make()
                                ->title('Reading validated successfully')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Validation failed')
                                ->body(implode(', ', $result['errors']))
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (MeterReading $record) => $record->validation_status === ValidationStatus::PENDING),
            ]);
    }
}
```

### 6. Batch Validation Action

```php
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class MeterReadingResource extends Resource
{
    public static function table(Table $table): Table
    {
        return $table
            // ... columns definition
            ->bulkActions([
                BulkAction::make('batch_validate')
                    ->label('Validate Selected')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (Collection $records) {
                        $validator = app(ServiceValidationEngine::class);
                        
                        try {
                            $result = $validator->batchValidateReadings($records);
                            
                            // Update validation status for valid readings
                            $validReadingIds = [];
                            foreach ($result['results'] as $readingId => $validationResult) {
                                if ($validationResult['is_valid']) {
                                    $validReadingIds[] = $readingId;
                                }
                            }
                            
                            MeterReading::whereIn('id', $validReadingIds)
                                ->update(['validation_status' => ValidationStatus::VALIDATED]);
                            
                            // Show summary notification
                            Notification::make()
                                ->title('Batch validation completed')
                                ->body(sprintf(
                                    'Validated %d of %d readings (%.1f%% success rate)',
                                    $result['valid_readings'],
                                    $result['total_readings'],
                                    $result['summary']['validation_rate']
                                ))
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Batch validation failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Batch Validate Readings')
                    ->modalDescription('This will validate all selected meter readings. Invalid readings will remain unchanged.')
            ]);
    }
}
```

## API Integration Examples

### 7. REST API Controller

```php
use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateMeterReadingRequest;
use App\Http\Requests\BatchValidateRequest;

class ValidationApiController extends Controller
{
    public function __construct(
        private readonly ServiceValidationEngine $validator
    ) {}

    /**
     * Validate a single meter reading.
     */
    public function validateReading(ValidateMeterReadingRequest $request, MeterReading $reading)
    {
        $serviceConfig = null;
        
        if ($request->has('service_configuration_id')) {
            $serviceConfig = ServiceConfiguration::findOrFail(
                $request->service_configuration_id
            );
        }

        $result = $this->validator->validateMeterReading($reading, $serviceConfig);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Batch validate multiple readings.
     */
    public function batchValidate(BatchValidateRequest $request)
    {
        $readings = MeterReading::whereIn('id', $request->reading_ids)->get();
        
        $result = $this->validator->batchValidateReadings(
            $readings, 
            $request->validation_options ?? []
        );

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Validate rate change.
     */
    public function validateRateChange(Request $request, ServiceConfiguration $config)
    {
        $request->validate([
            'new_rate_schedule' => 'required|array',
            'new_rate_schedule.rate_per_unit' => 'required|numeric|min:0',
        ]);

        $result = $this->validator->validateRateChangeRestrictions(
            $config,
            $request->new_rate_schedule
        );

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
```

### 8. Request Validation Classes

```php
use Illuminate\Foundation\Http\FormRequest;

class ValidateMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('reading'));
    }

    public function rules(): array
    {
        return [
            'service_configuration_id' => 'sometimes|exists:service_configurations,id',
            'validation_options' => 'sometimes|array',
            'validation_options.skip_seasonal_validation' => 'sometimes|boolean',
            'validation_options.strict_mode' => 'sometimes|boolean',
            'validation_options.include_recommendations' => 'sometimes|boolean',
        ];
    }
}

class BatchValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user can view all requested readings
        $readingIds = $this->reading_ids ?? [];
        $readings = MeterReading::whereIn('id', $readingIds)->get();
        
        return $readings->every(fn($reading) => $this->user()->can('view', $reading));
    }

    public function rules(): array
    {
        return [
            'reading_ids' => 'required|array|min:1|max:100',
            'reading_ids.*' => 'exists:meter_readings,id',
            'validation_options' => 'sometimes|array',
            'validation_options.parallel_processing' => 'sometimes|boolean',
            'validation_options.include_performance_metrics' => 'sometimes|boolean',
            'validation_options.stop_on_first_error' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'reading_ids.max' => 'Cannot validate more than 100 readings at once.',
            'reading_ids.*.exists' => 'One or more meter readings do not exist.',
        ];
    }
}
```

## Testing Examples

### 9. Unit Testing

```php
use Tests\TestCase;
use App\Services\ServiceValidationEngine;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;

class ServiceValidationEngineTest extends TestCase
{
    private ServiceValidationEngine $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app(ServiceValidationEngine::class);
    }

    public function test_validates_normal_consumption_reading()
    {
        $reading = MeterReading::factory()->create([
            'value' => 150, // Normal consumption
            'reading_date' => now(),
        ]);

        $result = $this->validator->validateMeterReading($reading);

        $this->assertTrue($result['is_valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_rejects_excessive_consumption_reading()
    {
        $reading = MeterReading::factory()->create([
            'value' => 50000, // Excessive consumption
            'reading_date' => now(),
        ]);

        $result = $this->validator->validateMeterReading($reading);

        $this->assertFalse($result['is_valid']);
        $this->assertContains('Consumption exceeds maximum allowed limit', $result['errors']);
    }

    public function test_batch_validation_performance()
    {
        $readings = MeterReading::factory()->count(50)->create();

        $startTime = microtime(true);
        $result = $this->validator->batchValidateReadings($readings);
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration); // Should complete in under 1 second
        $this->assertEquals(50, $result['total_readings']);
        $this->assertArrayHasKey('performance_metrics', $result);
    }

    public function test_rate_change_validation_with_restrictions()
    {
        $config = ServiceConfiguration::factory()->create();
        
        // Try to change rate too soon (within 30 days)
        $newRateSchedule = [
            'rate_per_unit' => 0.20,
            'effective_from' => now()->addDays(5)->toDateString(),
        ];

        $result = $this->validator->validateRateChangeRestrictions($config, $newRateSchedule);

        $this->assertFalse($result['is_valid']);
        $this->assertContains('Rate change requires 30 days between changes', $result['errors']);
    }
}
```

### 10. Integration Testing

```php
class ValidationIntegrationTest extends TestCase
{
    public function test_validation_integrates_with_gyvatukas_system()
    {
        // Create heating service configuration (gyvatukas compatible)
        $heatingService = UtilityService::factory()->create([
            'service_type_bridge' => ServiceType::HEATING,
            'name' => 'Heating Service',
        ]);

        $config = ServiceConfiguration::factory()->create([
            'utility_service_id' => $heatingService->id,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
        ]);

        $reading = MeterReading::factory()->create([
            'meter' => Meter::factory()->create([
                'service_configuration_id' => $config->id,
                'type' => MeterType::HEATING,
            ]),
            'value' => 250, // Typical heating consumption
        ]);

        // Validate using universal system
        $universalResult = $this->validator->validateMeterReading($reading, $config);

        // Ensure gyvatukas integration works
        $this->assertTrue($universalResult['is_valid']);
        $this->assertArrayHasKey('validation_metadata', $universalResult);
        
        // Check that seasonal validation was applied (gyvatukas feature)
        $metadata = $universalResult['validation_metadata'];
        $this->assertContains('seasonal', $metadata['validators_applied'] ?? []);
    }

    public function test_multi_tenant_isolation_in_validation()
    {
        // Create readings for different tenants
        $tenant1Reading = MeterReading::factory()->create(['tenant_id' => 1]);
        $tenant2Reading = MeterReading::factory()->create(['tenant_id' => 2]);

        // Act as tenant 1 user
        $this->actingAs(User::factory()->create(['tenant_id' => 1]));

        // Should be able to validate own tenant's reading
        $result1 = $this->validator->validateMeterReading($tenant1Reading);
        $this->assertTrue($result1['is_valid']);

        // Should not be able to validate other tenant's reading
        $result2 = $this->validator->validateMeterReading($tenant2Reading);
        $this->assertFalse($result2['is_valid']);
        $this->assertContains('Unauthorized', $result2['errors'][0] ?? '');
    }
}
```

## Performance Optimization Examples

### 11. Caching Strategy Implementation

```php
class OptimizedValidationService
{
    public function __construct(
        private readonly ServiceValidationEngine $validator,
        private readonly CacheManager $cache
    ) {}

    public function validateWithCaching(MeterReading $reading): array
    {
        // Create cache key based on reading attributes
        $cacheKey = $this->buildValidationCacheKey($reading);
        
        // Try to get cached result
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult !== null) {
            // Add cache hit metadata
            $cachedResult['validation_metadata']['cache_hit'] = true;
            return $cachedResult;
        }

        // Perform validation
        $result = $this->validator->validateMeterReading($reading);
        
        // Cache result if valid (don't cache errors as they might be transient)
        if ($result['is_valid']) {
            $this->cache->put($cacheKey, $result, now()->addMinutes(15));
        }

        $result['validation_metadata']['cache_hit'] = false;
        return $result;
    }

    private function buildValidationCacheKey(MeterReading $reading): string
    {
        return sprintf(
            'validation:%s:%s:%s:%s',
            $reading->meter_id,
            $reading->value,
            $reading->reading_date->format('Y-m-d'),
            $reading->zone ?? 'default'
        );
    }
}
```

### 12. Async Validation for Large Batches

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AsyncBatchValidationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly array $readingIds,
        private readonly int $userId,
        private readonly string $notificationChannel = 'mail'
    ) {}

    public function handle(ServiceValidationEngine $validator): void
    {
        $readings = MeterReading::whereIn('id', $this->readingIds)->get();
        
        // Process in chunks to manage memory
        $results = [];
        $readings->chunk(25)->each(function ($chunk) use ($validator, &$results) {
            $chunkResult = $validator->batchValidateReadings($chunk);
            $results[] = $chunkResult;
        });

        // Aggregate results
        $aggregatedResult = $this->aggregateResults($results);

        // Notify user of completion
        $user = User::find($this->userId);
        $user->notify(new BatchValidationCompletedNotification($aggregatedResult));
    }

    private function aggregateResults(array $results): array
    {
        $aggregated = [
            'total_readings' => 0,
            'valid_readings' => 0,
            'invalid_readings' => 0,
            'warnings_count' => 0,
            'results' => [],
        ];

        foreach ($results as $result) {
            $aggregated['total_readings'] += $result['total_readings'];
            $aggregated['valid_readings'] += $result['valid_readings'];
            $aggregated['invalid_readings'] += $result['invalid_readings'];
            $aggregated['warnings_count'] += $result['warnings_count'];
            $aggregated['results'] = array_merge($aggregated['results'], $result['results']);
        }

        return $aggregated;
    }
}

// Usage
class BatchValidationController extends Controller
{
    public function submitBatchValidation(Request $request)
    {
        $readingIds = $request->reading_ids;
        
        // Dispatch async job for large batches
        if (count($readingIds) > 50) {
            AsyncBatchValidationJob::dispatch(
                $readingIds,
                auth()->id(),
                $request->notification_channel ?? 'mail'
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch validation started. You will be notified when complete.',
                'async' => true
            ]);
        }

        // Process synchronously for small batches
        $readings = MeterReading::whereIn('id', $readingIds)->get();
        $validator = app(ServiceValidationEngine::class);
        $result = $validator->batchValidateReadings($readings);

        return response()->json([
            'success' => true,
            'data' => $result,
            'async' => false
        ]);
    }
}
```

These examples demonstrate the comprehensive capabilities of the ServiceValidationEngine across different use cases, from simple validations to complex batch processing and integration scenarios.