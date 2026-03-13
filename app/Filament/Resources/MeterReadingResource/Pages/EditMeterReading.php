<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
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
    public function form(Schema $schema): Schema
    {
        $minLength = config('billing.validation.change_reason_min_length', 10);
        $maxLength = config('billing.validation.change_reason_max_length', 500);
        
        return $schema
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->label(__('meter_readings.labels.reading_value'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix(__('meter_readings.units'))
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
                                $fail(__('meter_readings.validation.custom.monotonicity_lower', [
                                    'previous' => $previousReading->value,
                                ]));
                            }

                            // Validate against next reading
                            $nextReading = $service->getAdjacentReading($reading, $zone, 'next');
                            if ($nextReading && $value > $nextReading->value) {
                                $fail(__('meter_readings.validation.custom.monotonicity_higher', [
                                    'next' => $nextReading->value,
                                ]));
                            }
                        },
                    ])
                    ->validationMessages([
                        'required' => __('meter_readings.validation.value.required'),
                        'numeric' => __('meter_readings.validation.value.numeric'),
                        'min' => __('meter_readings.validation.value.min'),
                    ]),
                
                Forms\Components\Textarea::make('change_reason')
                    ->label(__('meter_readings.labels.reason'))
                    ->required()
                    ->minLength($minLength)
                    ->maxLength($maxLength)
                    ->helperText(__('meter_readings.helper_text.change_reason', ['min' => $minLength]))
                    ->validationMessages([
                        'required' => __('meter_readings.validation.change_reason.required'),
                        'min' => __('meter_readings.validation.change_reason.min', ['min' => $minLength]),
                        'max' => __('meter_readings.validation.change_reason.max', ['max' => $maxLength]),
                    ]),
                
                Forms\Components\DatePicker::make('reading_date')
                    ->label(__('meter_readings.labels.reading_date'))
                    ->maxDate(now())
                    ->native(false)
                    ->validationMessages([
                        'date' => __('meter_readings.validation.reading_date.date'),
                        'before_or_equal' => __('meter_readings.validation.reading_date.before_or_equal'),
                    ]),
                
                Forms\Components\TextInput::make('zone')
                    ->label(__('meter_readings.labels.zone'))
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->helperText(__('meter_readings.helper_text.zone_optional')),
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
