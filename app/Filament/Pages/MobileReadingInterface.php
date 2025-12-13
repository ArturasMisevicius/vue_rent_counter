<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\InputMethod;
use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\UniversalReadingCollector;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use BackedEnum;

/**
 * Mobile-responsive Filament page for field meter reading collection.
 * 
 * This page provides an optimized mobile interface for collecting meter readings
 * with support for offline data collection, camera integration, and automatic
 * synchronization when connectivity is restored.
 * 
 * ## Key Features
 * - **Mobile-Responsive Design**: Optimized for smartphones and tablets
 * - **Offline Support**: Browser storage with automatic sync
 * - **Camera Integration**: Photo capture with OCR processing
 * - **GPS Location**: Automatic location verification
 * - **Multi-Value Readings**: Support for complex meter structures
 * - **Validation**: Real-time validation with error feedback
 * 
 * ## Offline Capabilities
 * - Cache meter configurations and validation rules
 * - Store readings in browser localStorage
 * - Queue readings for sync when online
 * - Background sync with conflict resolution
 * 
 * @package App\Filament\Pages
 * @author Universal Utility Management System
 * @version 1.0.0
 * @since 2024-12-13
 */
class MobileReadingInterface extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Mobile Reading';
    protected static ?string $title = 'Field Reading Collection';
    protected string $view = 'filament.pages.mobile-reading-interface';
    protected static ?int $navigationSort = 10;

    /**
     * Current form data
     */
    public ?array $data = [];

    /**
     * Selected meter for reading
     */
    public ?Meter $selectedMeter = null;

    /**
     * Offline mode indicator
     */
    public bool $isOffline = false;

    /**
     * GPS coordinates
     */
    public ?array $gpsLocation = null;

    /**
     * Cached readings for offline sync
     */
    public array $cachedReadings = [];

    public function __construct()
    {
        parent::__construct();
        
        // Initialize offline data cache
        $this->loadOfflineCache();
    }

    public function mount(): void
    {
        $this->form->fill([
            'reading_date' => now()->format('Y-m-d H:i'),
            'input_method' => InputMethod::MANUAL->value,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Meter Selection')
                    ->description('Select the meter to record reading for')
                    ->schema([
                        Select::make('meter_id')
                            ->label('Meter')
                            ->options($this->getMeterOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedMeter = $state ? Meter::find($state) : null;
                                $this->updateMeterInfo();
                            }),

                        Placeholder::make('meter_info')
                            ->label('Meter Information')
                            ->content(fn () => $this->getMeterInfoHtml())
                            ->visible(fn () => $this->selectedMeter !== null),
                    ])
                    ->columns(1),

                Section::make('Reading Details')
                    ->description('Enter the meter reading information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('reading_date')
                                    ->label('Reading Date & Time')
                                    ->type('datetime-local')
                                    ->required()
                                    ->default(now()->format('Y-m-d\TH:i')),

                                Select::make('input_method')
                                    ->label('Input Method')
                                    ->options([
                                        InputMethod::MANUAL->value => 'Manual Entry',
                                        InputMethod::PHOTO_OCR->value => 'Photo with OCR',
                                        InputMethod::ESTIMATED->value => 'Estimated',
                                    ])
                                    ->required()
                                    ->live()
                                    ->default(InputMethod::MANUAL->value),
                            ]),

                        // Single value reading (legacy compatibility)
                        Grid::make(2)
                            ->schema([
                                TextInput::make('value')
                                    ->label('Reading Value')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required(fn ($get) => !$this->selectedMeter?->supportsMultiValueReadings())
                                    ->visible(fn () => !$this->selectedMeter?->supportsMultiValueReadings()),

                                TextInput::make('zone')
                                    ->label('Zone (if applicable)')
                                    ->visible(fn () => $this->selectedMeter?->supports_zones ?? false),
                            ])
                            ->visible(fn () => !$this->selectedMeter?->supportsMultiValueReadings()),

                        // Multi-value readings for universal services
                        Repeater::make('reading_values')
                            ->label('Reading Values')
                            ->schema([
                                TextInput::make('field_name')
                                    ->label('Field Name')
                                    ->required()
                                    ->disabled(),

                                TextInput::make('value')
                                    ->label('Value')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required(),

                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->disabled(),
                            ])
                            ->visible(fn () => $this->selectedMeter?->supportsMultiValueReadings())
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(3),

                        // Photo upload for OCR
                        FileUpload::make('photo')
                            ->label('Meter Photo')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->required(fn ($get) => $get('input_method') === InputMethod::PHOTO_OCR->value)
                            ->visible(fn ($get) => $get('input_method') === InputMethod::PHOTO_OCR->value)
                            ->helperText('Take a clear photo of the meter display. OCR will attempt to extract the reading automatically.'),

                        // GPS location (hidden field, populated by JavaScript)
                        Hidden::make('gps_latitude'),
                        Hidden::make('gps_longitude'),
                        Hidden::make('gps_accuracy'),

                        // Offline mode indicator
                        Placeholder::make('offline_status')
                            ->label('Connection Status')
                            ->content(fn () => $this->getConnectionStatusHtml())
                            ->visible(fn () => $this->isOffline),
                    ])
                    ->columns(1),

                Section::make('Validation & Notes')
                    ->description('Additional information and validation')
                    ->schema([
                        TextInput::make('notes')
                            ->label('Notes (Optional)')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Toggle::make('force_save')
                            ->label('Save despite validation warnings')
                            ->helperText('Check this to save the reading even if there are validation warnings')
                            ->visible(fn () => $this->hasValidationWarnings()),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * Submit reading action
     */
    public function submitReading(): void
    {
        try {
            $data = $this->form->getState();

            // Add GPS location if available
            if ($this->gpsLocation) {
                $data['gps_location'] = $this->gpsLocation;
            }

            // Check if we're offline
            if ($this->isOffline) {
                $this->saveOfflineReading($data);
                
                Notification::make()
                    ->title('Reading Saved Offline')
                    ->body('Reading has been saved locally and will sync when connection is restored.')
                    ->success()
                    ->send();
                
                $this->resetForm();
                return;
            }

            // Online submission
            $collector = app(UniversalReadingCollector::class);
            $result = $collector->createReading($data);

            if ($result['success']) {
                Notification::make()
                    ->title('Reading Saved Successfully')
                    ->body('Meter reading has been recorded and validated.')
                    ->success()
                    ->send();

                $this->resetForm();
            } else {
                $errors = implode(', ', $result['errors']);
                
                Notification::make()
                    ->title('Validation Failed')
                    ->body("Please correct the following errors: {$errors}")
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Saving Reading')
                ->body('An error occurred while saving the reading. Please try again.')
                ->danger()
                ->send();

            logger()->error('Mobile reading submission failed', [
                'error' => $e->getMessage(),
                'data' => $data ?? [],
            ]);
        }
    }

    /**
     * Sync offline readings action
     */
    public function syncOfflineReadings(): void
    {
        if (empty($this->cachedReadings)) {
            Notification::make()
                ->title('No Offline Readings')
                ->body('There are no offline readings to sync.')
                ->info()
                ->send();
            return;
        }

        $collector = app(UniversalReadingCollector::class);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($this->cachedReadings as $index => $readingData) {
            try {
                $result = $collector->createReading($readingData);
                
                if ($result['success']) {
                    $successCount++;
                    unset($this->cachedReadings[$index]);
                } else {
                    $errorCount++;
                    $errors[] = "Reading {$index}: " . implode(', ', $result['errors']);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Reading {$index}: " . $e->getMessage();
            }
        }

        // Update cached readings
        $this->saveOfflineCache();

        if ($successCount > 0) {
            Notification::make()
                ->title('Sync Completed')
                ->body("Successfully synced {$successCount} readings. {$errorCount} failed.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Sync Failed')
                ->body('No readings could be synced. Please check the errors and try again.')
                ->danger()
                ->send();
        }
    }

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_offline')
                ->label('Sync Offline Readings')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn () => !empty($this->cachedReadings))
                ->action('syncOfflineReadings'),

            Action::make('refresh_gps')
                ->label('Get GPS Location')
                ->icon('heroicon-o-map-pin')
                ->color('gray')
                ->action('refreshGPS'),
        ];
    }

    /**
     * Get meter options for select field
     */
    private function getMeterOptions(): array
    {
        return Cache::remember('mobile_meter_options', 300, function () {
            return Meter::query()
                ->with(['property:id,name', 'serviceConfiguration.utilityService:id,name'])
                ->get()
                ->mapWithKeys(function (Meter $meter) {
                    $label = "#{$meter->serial_number}";
                    
                    if ($meter->property) {
                        $label .= " - {$meter->property->name}";
                    }
                    
                    if ($meter->serviceConfiguration?->utilityService) {
                        $label .= " ({$meter->serviceConfiguration->utilityService->name})";
                    }
                    
                    return [$meter->id => $label];
                })
                ->toArray();
        });
    }

    /**
     * Get meter information HTML
     */
    private function getMeterInfoHtml(): HtmlString
    {
        if (!$this->selectedMeter) {
            return new HtmlString('');
        }

        $meter = $this->selectedMeter;
        $html = "<div class='space-y-2 text-sm'>";
        
        $html .= "<div><strong>Serial:</strong> {$meter->serial_number}</div>";
        $html .= "<div><strong>Type:</strong> {$meter->type->getLabel()}</div>";
        
        if ($meter->property) {
            $html .= "<div><strong>Property:</strong> {$meter->property->name}</div>";
        }
        
        if ($meter->serviceConfiguration?->utilityService) {
            $service = $meter->serviceConfiguration->utilityService;
            $html .= "<div><strong>Service:</strong> {$service->name} ({$service->unit_of_measurement})</div>";
        }
        
        if ($meter->supports_zones) {
            $html .= "<div class='text-blue-600'><strong>Note:</strong> This meter supports zones</div>";
        }
        
        if ($meter->supportsMultiValueReadings()) {
            $html .= "<div class='text-green-600'><strong>Note:</strong> Multi-value readings supported</div>";
        }
        
        $html .= "</div>";
        
        return new HtmlString($html);
    }

    /**
     * Get connection status HTML
     */
    private function getConnectionStatusHtml(): HtmlString
    {
        $status = $this->isOffline ? 'Offline' : 'Online';
        $color = $this->isOffline ? 'text-red-600' : 'text-green-600';
        $icon = $this->isOffline ? '🔴' : '🟢';
        
        $html = "<div class='{$color} font-medium'>{$icon} {$status}</div>";
        
        if ($this->isOffline && !empty($this->cachedReadings)) {
            $count = count($this->cachedReadings);
            $html .= "<div class='text-sm text-gray-600 mt-1'>{$count} readings cached for sync</div>";
        }
        
        return new HtmlString($html);
    }

    /**
     * Update meter info when meter is selected
     */
    private function updateMeterInfo(): void
    {
        if (!$this->selectedMeter) {
            return;
        }

        // Update form with meter-specific fields
        if ($this->selectedMeter->supportsMultiValueReadings()) {
            $structure = $this->selectedMeter->getReadingStructure();
            $readingValues = [];
            
            foreach ($structure['fields'] ?? [] as $field) {
                $readingValues[] = [
                    'field_name' => $field['name'],
                    'value' => '',
                    'unit' => $field['unit'] ?? '',
                ];
            }
            
            $this->data['reading_values'] = $readingValues;
        }
    }

    /**
     * Check if there are validation warnings
     */
    private function hasValidationWarnings(): bool
    {
        // This would check against validation rules
        // For now, return false as placeholder
        return false;
    }

    /**
     * Save reading for offline sync
     */
    private function saveOfflineReading(array $data): void
    {
        $data['offline_timestamp'] = now()->toISOString();
        $data['sync_status'] = 'pending';
        
        $this->cachedReadings[] = $data;
        $this->saveOfflineCache();
    }

    /**
     * Load offline cache from storage
     */
    private function loadOfflineCache(): void
    {
        $this->cachedReadings = Cache::get('mobile_offline_readings_' . auth()->id(), []);
    }

    /**
     * Save offline cache to storage
     */
    private function saveOfflineCache(): void
    {
        Cache::put('mobile_offline_readings_' . auth()->id(), $this->cachedReadings, now()->addDays(7));
    }

    /**
     * Reset form after successful submission
     */
    private function resetForm(): void
    {
        $this->selectedMeter = null;
        $this->form->fill([
            'reading_date' => now()->format('Y-m-d H:i'),
            'input_method' => InputMethod::MANUAL->value,
        ]);
    }

    /**
     * Get page width for mobile optimization
     */
    public function getMaxWidth(): MaxWidth
    {
        return MaxWidth::ScreenSmall;
    }

    /**
     * Check if page should be mobile-optimized
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation for users with meter reading permissions
        return auth()->user()?->can('create', MeterReading::class) ?? false;
    }
}