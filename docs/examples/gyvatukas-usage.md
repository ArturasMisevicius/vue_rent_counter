# GyvatukasCalculator Usage Examples

## Basic Usage Patterns

### Simple Calculation
```php
use App\Contracts\GyvatukasCalculatorInterface;
use App\Models\Building;
use Carbon\Carbon;

class BillingController extends Controller
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator
    ) {}
    
    public function calculateCirculation(Building $building, string $month): float
    {
        $date = Carbon::parse($month);
        return $this->calculator->calculate($building, $date);
    }
}
```

### Seasonal Calculations
```php
// Summer calculation (May-September)
$building = Building::find(1);
$summerMonth = Carbon::create(2024, 6, 1);
$summerEnergy = $calculator->calculateSummerGyvatukas($building, $summerMonth);
// Result: 142.5 kWh (base calculation)

// Winter calculation (October-April)
$winterMonth = Carbon::create(2024, 12, 1);
$winterEnergy = $calculator->calculateWinterGyvatukas($building, $winterMonth);
// Result: 185.25 kWh (with 30% peak winter adjustment)
```

## Advanced Usage Patterns

### Billing Service Integration
```php
class UtilitiesBillingService
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator,
        private readonly EnergyPricingService $pricing
    ) {}
    
    public function generateCirculationBills(Building $building, Carbon $month): Collection
    {
        // Calculate total circulation energy
        $totalEnergy = $this->calculator->calculate($building, $month);
        
        // Get current energy rate
        $ratePerKwh = $this->pricing->getRate($month, 'circulation');
        $totalCost = $totalEnergy * $ratePerKwh;
        
        // Distribute costs among properties
        $distribution = $this->calculator->distributeCirculationCost(
            $building, 
            $totalCost, 
            'area' // or 'equal'
        );
        
        // Create billing records
        return collect($distribution)->map(function ($cost, $propertyId) use ($month, $totalEnergy) {
            return [
                'property_id' => $propertyId,
                'billing_period' => $month->format('Y-m'),
                'circulation_cost' => round($cost, 2),
                'circulation_kwh' => $totalEnergy,
                'rate_per_kwh' => $ratePerKwh,
                'calculation_type' => $this->calculator->isSummerPeriod($month) ? 'summer' : 'winter'
            ];
        });
    }
}
```

### Batch Processing
```php
class GyvatukasBatchProcessor
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator
    ) {}
    
    public function processMonthlyCalculations(Carbon $month): void
    {
        Building::query()
            ->where('is_active', true)
            ->chunk(100, function ($buildings) use ($month) {
                foreach ($buildings as $building) {
                    try {
                        $energy = $this->calculator->calculate($building, $month);
                        
                        // Store calculation result
                        $building->circulation_calculations()->create([
                            'calculation_month' => $month,
                            'energy_kwh' => $energy,
                            'calculation_type' => $this->calculator->isSummerPeriod($month) ? 'summer' : 'winter',
                            'calculated_at' => now()
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Gyvatukas calculation failed', [
                            'building_id' => $building->id,
                            'month' => $month->format('Y-m'),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });
    }
}
```

## Filament Resource Integration

### Building Resource Actions
```php
// app/Filament/Resources/BuildingResource.php
use App\Contracts\GyvatukasCalculatorInterface;

class BuildingResource extends Resource
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate_summer_average')
                ->label(__('building.actions.recalculate_summer_average'))
                ->icon('heroicon-o-calculator')
                ->action(function (Building $record) {
                    $calculator = app(GyvatukasCalculatorInterface::class);
                    $average = $calculator->calculateAndStoreSummerAverage($record);
                    
                    Notification::make()
                        ->title(__('building.notifications.summer_average_updated'))
                        ->body(__('building.notifications.new_average', ['average' => $average]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('building.modals.recalculate_confirmation'))
                ->modalDescription(__('building.modals.recalculate_description')),
                
            Action::make('clear_cache')
                ->label(__('building.actions.clear_cache'))
                ->icon('heroicon-o-trash')
                ->action(function (Building $record) {
                    $calculator = app(GyvatukasCalculatorInterface::class);
                    $calculator->clearBuildingCache($record);
                    
                    Notification::make()
                        ->title(__('building.notifications.cache_cleared'))
                        ->success()
                        ->send();
                })
                ->color('warning')
                ->requiresConfirmation(),
        ];
    }
}
```

### Custom Filament Page
```php
// app/Filament/Pages/GyvatukasCalculations.php
class GyvatukasCalculations extends Page
{
    protected static string $view = 'filament.pages.gyvatukas-calculations';
    
    public Building $building;
    public Carbon $selectedMonth;
    public ?float $calculationResult = null;
    
    public function mount(): void
    {
        $this->selectedMonth = now()->startOfMonth();
    }
    
    public function calculate(): void
    {
        $calculator = app(GyvatukasCalculatorInterface::class);
        
        try {
            $this->calculationResult = $calculator->calculate(
                $this->building, 
                $this->selectedMonth
            );
            
            $this->dispatch('calculation-completed', [
                'result' => $this->calculationResult,
                'type' => $calculator->isSummerPeriod($this->selectedMonth) ? 'summer' : 'winter'
            ]);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('gyvatukas.calculation_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getFormSchema(): array
    {
        return [
            Select::make('building')
                ->relationship('building', 'name')
                ->required()
                ->searchable(),
                
            DatePicker::make('selectedMonth')
                ->label(__('gyvatukas.calculation_month'))
                ->required()
                ->displayFormat('Y-m')
                ->format('Y-m-01'),
        ];
    }
}
```

## Artisan Command Examples

### Summer Average Recalculation Command
```php
// app/Console/Commands/RecalculateSummerAverages.php
class RecalculateSummerAverages extends Command
{
    protected $signature = 'gyvatukas:recalculate-summer-averages 
                           {--building= : Specific building ID}
                           {--force : Force recalculation even if recent}';
    
    protected $description = 'Recalculate summer averages for buildings';
    
    public function handle(GyvatukasCalculatorInterface $calculator): int
    {
        $buildingId = $this->option('building');
        $force = $this->option('force');
        
        $query = Building::query()->where('is_active', true);
        
        if ($buildingId) {
            $query->where('id', $buildingId);
        }
        
        $buildings = $query->get();
        $this->info("Processing {$buildings->count()} buildings...");
        
        $progressBar = $this->output->createProgressBar($buildings->count());
        
        foreach ($buildings as $building) {
            try {
                // Skip if recently calculated and not forced
                if (!$force && $this->isRecentlyCalculated($building)) {
                    $this->line("\nSkipping building {$building->id} (recently calculated)");
                    continue;
                }
                
                $average = $calculator->calculateAndStoreSummerAverage($building);
                
                $this->line("\nBuilding {$building->id}: {$average} kWh average");
                
            } catch (\Exception $e) {
                $this->error("\nFailed to calculate for building {$building->id}: {$e->getMessage()}");
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info('Summer average recalculation completed!');
        
        return Command::SUCCESS;
    }
    
    private function isRecentlyCalculated(Building $building): bool
    {
        return $building->gyvatukas_last_calculated?->isAfter(now()->subMonths(6)) ?? false;
    }
}
```

### Cache Management Command
```php
// app/Console/Commands/ManageGyvatukasCache.php
class ManageGyvatukasCache extends Command
{
    protected $signature = 'gyvatukas:cache 
                           {action : Action to perform (clear|warm|status)}
                           {--building= : Specific building ID for clear action}';
    
    protected $description = 'Manage gyvatukas calculation cache';
    
    public function handle(GyvatukasCalculatorInterface $calculator): int
    {
        $action = $this->argument('action');
        
        return match ($action) {
            'clear' => $this->clearCache($calculator),
            'warm' => $this->warmCache($calculator),
            'status' => $this->showCacheStatus($calculator),
            default => $this->error("Unknown action: {$action}")
        };
    }
    
    private function clearCache(GyvatukasCalculatorInterface $calculator): int
    {
        $buildingId = $this->option('building');
        
        if ($buildingId) {
            $building = Building::findOrFail($buildingId);
            $calculator->clearBuildingCache($building);
            $this->info("Cache cleared for building {$buildingId}");
        } else {
            $calculator->clearAllCache();
            $this->info('All gyvatukas cache cleared');
        }
        
        return Command::SUCCESS;
    }
    
    private function warmCache(GyvatukasCalculatorInterface $calculator): int
    {
        $currentMonth = now()->startOfMonth();
        $buildings = Building::where('is_active', true)->limit(10)->get();
        
        $this->info("Warming cache for {$buildings->count()} buildings...");
        
        foreach ($buildings as $building) {
            try {
                $calculator->calculate($building, $currentMonth);
                $this->line("Warmed cache for building {$building->id}");
            } catch (\Exception $e) {
                $this->error("Failed to warm cache for building {$building->id}: {$e->getMessage()}");
            }
        }
        
        return Command::SUCCESS;
    }
}
```

## API Integration Examples

### REST API Controller
```php
// app/Http/Controllers/Api/GyvatukasController.php
class GyvatukasController extends Controller
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator
    ) {}
    
    /**
     * Calculate circulation energy for a building and month
     * 
     * @param Building $building
     * @param CalculateGyvatukasRequest $request
     * @return JsonResponse
     */
    public function calculate(Building $building, CalculateGyvatukasRequest $request): JsonResponse
    {
        $month = Carbon::parse($request->validated('month'));
        
        try {
            $energy = $this->calculator->calculate($building, $month);
            $isSummer = $this->calculator->isSummerPeriod($month);
            
            return response()->json([
                'data' => [
                    'building_id' => $building->id,
                    'calculation_month' => $month->format('Y-m'),
                    'circulation_energy_kwh' => $energy,
                    'calculation_type' => $isSummer ? 'summer' : 'winter',
                    'is_summer_period' => $isSummer,
                    'calculated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Calculation failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Get summer average for a building
     */
    public function summerAverage(Building $building): JsonResponse
    {
        try {
            $average = $this->calculator->getSummerAverage($building);
            
            return response()->json([
                'data' => [
                    'building_id' => $building->id,
                    'summer_average_kwh' => $average,
                    'last_calculated' => $building->gyvatukas_last_calculated?->toISOString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get summer average',
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Distribute circulation costs among properties
     */
    public function distributeCosts(Building $building, DistributeCostsRequest $request): JsonResponse
    {
        $totalCost = $request->validated('total_cost');
        $method = $request->validated('method', 'equal');
        
        try {
            $distribution = $this->calculator->distributeCirculationCost(
                $building, 
                $totalCost, 
                $method
            );
            
            return response()->json([
                'data' => [
                    'building_id' => $building->id,
                    'total_cost' => $totalCost,
                    'distribution_method' => $method,
                    'property_costs' => $distribution,
                    'properties_count' => count($distribution)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Cost distribution failed',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
```

### Form Request Validation
```php
// app/Http/Requests/CalculateGyvatukasRequest.php
class CalculateGyvatukasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('calculate-gyvatukas', $this->route('building'));
    }
    
    public function rules(): array
    {
        return [
            'month' => [
                'required',
                'date_format:Y-m',
                'after_or_equal:2020-01',
                'before_or_equal:' . now()->format('Y-m')
            ]
        ];
    }
    
    public function messages(): array
    {
        return [
            'month.required' => __('gyvatukas.validation.month_required'),
            'month.date_format' => __('gyvatukas.validation.month_format'),
            'month.after_or_equal' => __('gyvatukas.validation.month_too_old'),
            'month.before_or_equal' => __('gyvatukas.validation.month_future'),
        ];
    }
}
```

## Testing Examples

### Feature Test
```php
// tests/Feature/GyvatukasCalculationTest.php
class GyvatukasCalculationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_calculate_summer_gyvatukas_via_api(): void
    {
        $building = Building::factory()->create(['total_apartments' => 20]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson("/api/buildings/{$building->id}/gyvatukas/calculate", [
                'month' => '2024-06'
            ]);
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'building_id',
                    'calculation_month',
                    'circulation_energy_kwh',
                    'calculation_type',
                    'is_summer_period'
                ]
            ])
            ->assertJson([
                'data' => [
                    'building_id' => $building->id,
                    'calculation_month' => '2024-06',
                    'calculation_type' => 'summer',
                    'is_summer_period' => true
                ]
            ]);
    }
    
    public function test_distributes_costs_correctly(): void
    {
        $building = Building::factory()->create();
        
        // Create properties with different areas
        Property::factory()->create([
            'building_id' => $building->id,
            'area_sqm' => 50.0
        ]);
        Property::factory()->create([
            'building_id' => $building->id,
            'area_sqm' => 75.0
        ]);
        
        $calculator = app(GyvatukasCalculatorInterface::class);
        $distribution = $calculator->distributeCirculationCost($building, 1000.0, 'area');
        
        expect($distribution)->toHaveCount(2)
            ->and(array_sum($distribution))->toBe(1000.0)
            ->and($distribution)->toMatchArray([
                1 => 400.0, // 50/125 * 1000
                2 => 600.0  // 75/125 * 1000
            ]);
    }
}
```

## Performance Optimization Examples

### Bulk Calculation with Caching
```php
class OptimizedGyvatukasProcessor
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator
    ) {}
    
    public function processBuildingsEfficiently(Collection $buildings, Carbon $month): array
    {
        $results = [];
        
        // Pre-warm cache for all buildings
        $this->preWarmCache($buildings, $month);
        
        // Process in chunks to manage memory
        $buildings->chunk(50)->each(function ($chunk) use ($month, &$results) {
            foreach ($chunk as $building) {
                $results[$building->id] = $this->calculator->calculate($building, $month);
            }
        });
        
        return $results;
    }
    
    private function preWarmCache(Collection $buildings, Carbon $month): void
    {
        // Batch load building data to reduce queries
        $buildings->load(['properties:id,building_id,area_sqm']);
        
        // Pre-calculate summer averages if needed
        $needsAverage = $buildings->filter(function ($building) {
            return $building->gyvatukas_summer_average === null ||
                   $building->gyvatukas_last_calculated?->isBefore(now()->subMonths(12));
        });
        
        foreach ($needsAverage as $building) {
            $this->calculator->calculateAndStoreSummerAverage($building);
        }
    }
}
```

These examples demonstrate the flexibility and power of the GyvatukasCalculator service across different contexts and use cases in the Lithuanian utilities billing system.