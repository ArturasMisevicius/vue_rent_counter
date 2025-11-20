<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditMeterReading extends EditRecord
{
    protected static string $resource = MeterReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Customize the form for editing to include change_reason.
     * Integrates validation rules from UpdateMeterReadingRequest.
     */
    public function form(Form $form): Form
    {
        $minLength = config('billing.validation.change_reason_min_length', 10);
        $maxLength = config('billing.validation.change_reason_max_length', 500);
        
        return $form
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->label('Reading Value')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('units')
                    ->live(onBlur: true)
                    ->rules([
                        // Validation from UpdateMeterReadingRequest
                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                            $reading = $this->record;
                            
                            if (!$reading instanceof MeterReading) {
                                return;
                            }

                            $zone = $this->data['zone'] ?? $reading->zone;
                            $service = app(MeterReadingService::class);

                            // Validate against previous reading
                            $previousReading = $service->getAdjacentReading($reading, $zone, 'previous');
                            if ($previousReading && $value < $previousReading->value) {
                                $fail("Reading cannot be lower than previous reading ({$previousReading->value})");
                            }

                            // Validate against next reading
                            $nextReading = $service->getAdjacentReading($reading, $zone, 'next');
                            if ($nextReading && $value > $nextReading->value) {
                                $fail("Reading cannot be higher than next reading ({$nextReading->value})");
                            }
                        },
                    ])
                    ->validationMessages([
                        'required' => 'Meter reading is required',
                        'numeric' => 'Reading must be a number',
                        'min' => 'Reading must be a positive number',
                    ]),
                
                Forms\Components\Textarea::make('change_reason')
                    ->label('Change Reason')
                    ->required()
                    ->minLength($minLength)
                    ->maxLength($maxLength)
                    ->helperText("Explain why this reading is being modified (minimum {$minLength} characters)")
                    ->validationMessages([
                        'required' => 'Change reason is required for audit trail',
                        'min' => "Change reason must be at least {$minLength} characters",
                        'max' => "Change reason must not exceed {$maxLength} characters",
                    ]),
                
                Forms\Components\DatePicker::make('reading_date')
                    ->label('Reading Date')
                    ->maxDate(now())
                    ->native(false)
                    ->validationMessages([
                        'date' => 'Reading date must be a valid date',
                        'before_or_equal' => 'Reading date cannot be in the future',
                    ]),
                
                Forms\Components\TextInput::make('zone')
                    ->label('Zone')
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->helperText('Optional: For multi-zone meters (e.g., day/night)'),
            ]);
    }

    /**
     * Mutate form data before saving.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store the change_reason in the model's temporary attribute
        if (isset($data['change_reason'])) {
            $this->record->change_reason = $data['change_reason'];
            unset($data['change_reason']);
        }
        
        return $data;
    }
}
