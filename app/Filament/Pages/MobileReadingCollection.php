<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\UniversalReadingCollector;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;
use BackedEnum;

/**
 * Mobile-responsive Filament page for field meter reading collection.
 * 
 * Features:
 * - Mobile-optimized forms with large touch targets
 * - Camera integration for meter photo capture
 * - Offline data collection with browser storage sync
 * - GPS location verification
 * - Automatic reading extraction from photos (OCR)
 * - Multi-value reading support for universal services
 */
class MobileReadingCollection extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';
    
    protected static ?string $navigationLabel = 'Mobile Reading Collection';
    
    protected static ?string $title = 'Field Reading Collection';
    
    protected string $view = 'filament.pages.mobile-reading-collection';
    
    protected static string|UnitEnum|null $navigationGroup = 'Field Operations';
    
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    
    public ?Meter $selectedMeter = null;
    
    public bool $offlineMode = false;
    
    public ?array $gpsLocation = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Meter Selection')
                    ->schema([
                        Forms\Components\Select::make('meter_id')
                            ->label('Select Meter')
                            ->relationship('meter', 'serial_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->serial_number} - {$record->property->building->name} Unit {$record->property->unit_number}"
                            )
                            ->searchable(['serial_number', 'property.unit_number'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->selectedMeter = Meter::with(['serviceConfiguration.utilityService', 'property.building'])->find($state);
                                    $this->dispatch('meter-selected', meterId: $state);
                                }
                            })
                            ->extraAttributes([
                                'class' => 'mobile-select-large',
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Reading Information')
                    ->schema([
                        Forms\Components\DatePicker::make('reading_date')
                            ->label('Reading Date')
                            ->default(now())
                            ->maxDate(now())
                            ->required()
                            ->native(false)
                            ->extraAttributes([
                                'class' => 'mobile-input-large',
                            ]),

                        $this->getReadingInputComponent(),

                        Forms\Components\Select::make('input_method')
                            ->label('Input Method')
                            ->options([
                                InputMethod::MANUAL->value => 'Manual Entry',
                                InputMethod::PHOTO_OCR->value => 'Photo with OCR',
                            ])
                            ->default(InputMethod::MANUAL)
                            ->required()
                            ->live()
                            ->extraAttributes([
                                'class' => 'mobile-select-large',
                            ]),

                        Forms\Components\FileUpload::make('photo')
                            ->label('Meter Photo')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->directory('meter-photos')
                            ->visibility('private')
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(5120) // 5MB
                            ->visible(fn (Get $get) => $get('input_method') === InputMethod::PHOTO_OCR->value)
                            ->afterStateUpdated(function (TemporaryUploadedFile $state = null) {
                                if ($state) {
                                    $this->dispatch('photo-uploaded', path: $state->getPathname());
                                }
                            })
                            ->extraAttributes([
                                'class' => 'mobile-file-upload',
                            ]),

                        Forms\Components\TextInput::make('zone')
                            ->label('Tariff Zone')
                            ->placeholder('e.g., day, night')
                            ->visible(fn () => $this->selectedMeter?->supports_zones ?? false)
                            ->extraAttributes([
                                'class' => 'mobile-input-large',
                            ]),

                        Forms\Components\Toggle::make('is_estimated')
                            ->label('Estimated Reading')
                            ->helperText('Check if this is an estimated reading')
                            ->extraAttributes([
                                'class' => 'mobile-toggle-large',
                            ]),
                    ])
                    ->visible(fn (Get $get) => filled($get('meter_id')))
                    ->columns(1),

                Forms\Components\Section::make('Location & Notes')
                    ->schema([
                        Forms\Components\Placeholder::make('gps_status')
                            ->label('GPS Location')
                            ->content(new HtmlString('<div id="gps-status">Detecting location...</div>'))
                            ->extraAttributes([
                                'class' => 'mobile-gps-status',
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Any additional notes about this reading...')
                            ->rows(3)
                            ->extraAttributes([
                                'class' => 'mobile-textarea-large',
                            ]),
                    ])
                    ->visible(fn (Get $get) => filled($get('meter_id')))
                    ->collapsible()
                    ->persistCollapsed(),
            ])
            ->statePath('data')
            ->model(MeterReading::class);
    }

    protected function getReadingInputComponent(): Component
    {
        // Check if selected meter supports multi-value readings
        if ($this->selectedMeter?->supportsMultiValueReadings()) {
            return $this->getMultiValueReadingComponent();
        }

        return Forms\Components\TextInput::make('value')
            ->label('Reading Value')
            ->numeric()
            ->step(0.01)
            ->minValue(0)
            ->required()
            ->suffix($this->selectedMeter?->getUtilityService()?->unit_of_measurement ?? '')
            ->extraAttributes([
                'class' => 'mobile-input-large mobile-numeric',
                'inputmode' => 'decimal',
            ]);
    }

    protected function getMultiValueReadingComponent(): Component
    {
        $structure = $this->selectedMeter?->getReadingStructure() ?? [];
        $fields = $structure['fields'] ?? [];

        if (empty($fields)) {
            return $this->getSingleValueComponent();
        }

        $components = [];
        foreach ($fields as $field) {
            $components[] = Forms\Components\TextInput::make("reading_values.{$field['name']}")
                ->label($field['label'] ?? $field['name'])
                ->numeric()
                ->step(0.01)
                ->minValue($field['min'] ?? 0)
                ->maxValue($field['max'] ?? null)
                ->required($field['required'] ?? false)
                ->suffix($field['unit'] ?? '')
                ->helperText($field['description'] ?? null)
                ->extraAttributes([
                    'class' => 'mobile-input-large mobile-numeric',
                    'inputmode' => 'decimal',
                ]);
        }

        return Forms\Components\Group::make($components)
            ->label('Reading Values');
    }

    protected function getSingleValueComponent(): Component
    {
        return Forms\Components\TextInput::make('value')
            ->label('Reading Value')
            ->numeric()
            ->step(0.01)
            ->minValue(0)
            ->required()
            ->suffix($this->selectedMeter?->getUtilityService()?->unit_of_measurement ?? '')
            ->extraAttributes([
                'class' => 'mobile-input-large mobile-numeric',
                'inputmode' => 'decimal',
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleOfflineMode')
                ->label(fn () => $this->offlineMode ? 'Go Online' : 'Go Offline')
                ->icon(fn () => $this->offlineMode ? 'heroicon-o-wifi' : 'heroicon-o-wifi-slash')
                ->color(fn () => $this->offlineMode ? 'success' : 'warning')
                ->action(function () {
                    $this->offlineMode = !$this->offlineMode;
                    $this->dispatch('offline-mode-toggled', offline: $this->offlineMode);
                }),

            Action::make('syncOfflineData')
                ->label('Sync Offline Data')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn () => $this->offlineMode)
                ->action(function () {
                    $this->dispatch('sync-offline-data');
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Reading')
                ->icon('heroicon-o-check')
                ->color('success')
                ->size('xl')
                ->extraAttributes([
                    'class' => 'mobile-button-large w-full',
                ])
                ->action('saveReading'),

            Action::make('saveAndNext')
                ->label('Save & Next Meter')
                ->icon('heroicon-o-arrow-right')
                ->color('primary')
                ->size('xl')
                ->extraAttributes([
                    'class' => 'mobile-button-large w-full mt-2',
                ])
                ->action('saveAndNext'),

            Action::make('clear')
                ->label('Clear Form')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'mobile-button-large w-full mt-2',
                ])
                ->action(function () {
                    $this->form->fill();
                    $this->selectedMeter = null;
                }),
        ];
    }

    public function saveReading(): void
    {
        try {
            $data = $this->form->getState();
            
            // Validate required fields
            if (empty($data['meter_id']) || empty($data['reading_date'])) {
                Notification::make()
                    ->title('Validation Error')
                    ->body('Please select a meter and enter a reading date.')
                    ->danger()
                    ->send();
                return;
            }

            // Get the reading collector service
            $collector = app(UniversalReadingCollector::class);
            
            // Prepare reading data
            $readingData = [
                'meter_id' => $data['meter_id'],
                'reading_date' => $data['reading_date'],
                'input_method' => $data['input_method'] ?? InputMethod::MANUAL,
                'validation_status' => ValidationStatus::PENDING,
                'entered_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
                'zone' => $data['zone'] ?? null,
                'gps_location' => $this->gpsLocation,
            ];

            // Handle reading values (single or multi-value)
            if (isset($data['reading_values']) && is_array($data['reading_values'])) {
                $readingData['reading_values'] = $data['reading_values'];
                // Set primary value for backward compatibility
                $readingData['value'] = array_sum(array_filter($data['reading_values'], 'is_numeric'));
            } else {
                $readingData['value'] = $data['value'] ?? 0;
            }

            // Handle photo upload
            if (isset($data['photo']) && $data['photo']) {
                $photoPath = $data['photo']->store('meter-photos', 'private');
                $readingData['photo_path'] = $photoPath;
            }

            // Save the reading
            $result = $collector->createReading($readingData);

            if ($result['success']) {
                $reading = $result['reading'];
                
                Notification::make()
                    ->title('Reading Saved')
                    ->body("Reading for meter {$this->selectedMeter->serial_number} saved successfully.")
                    ->success()
                    ->send();

                // Log the action
                logger()->info('Mobile reading collected', [
                    'reading_id' => $reading->id,
                    'meter_id' => $reading->meter_id,
                    'user_id' => auth()->id(),
                    'input_method' => $reading->input_method->value,
                ]);
            } else {
                Notification::make()
                    ->title('Error Saving Reading')
                    ->body('Errors: ' . implode(', ', $result['errors']))
                    ->danger()
                    ->send();
                return;
            }

            // Clear form for next reading
            $this->form->fill();
            $this->selectedMeter = null;

        } catch (\Exception $e) {
            logger()->error('Mobile reading collection failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'data' => $data ?? [],
            ]);

            Notification::make()
                ->title('Error Saving Reading')
                ->body('An error occurred while saving the reading. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function saveAndNext(): void
    {
        $this->saveReading();
        
        // Auto-select next meter if available
        $currentMeterId = $this->data['meter_id'] ?? null;
        if ($currentMeterId) {
            $nextMeter = Meter::where('id', '>', $currentMeterId)
                ->orderBy('id')
                ->first();
                
            if ($nextMeter) {
                $this->data['meter_id'] = $nextMeter->id;
                $this->selectedMeter = $nextMeter;
                $this->form->fill($this->data);
            }
        }
    }

    public function getMaxContentWidth(): string
    {
        return 'md';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', MeterReading::class) ?? false;
    }
}
