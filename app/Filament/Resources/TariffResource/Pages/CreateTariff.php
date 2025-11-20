<?php

namespace App\Filament\Resources\TariffResource\Pages;

use App\Filament\Resources\TariffResource;
use App\Services\TimeRangeValidator;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTariff extends CreateRecord
{
    protected static string $resource = TariffResource::class;

    /**
     * Validate the form data before creating.
     * Integrates validation rules from StoreTariffRequest.
     */
    protected function beforeValidate(): void
    {
        $data = $this->form->getState();
        
        // Validate time-of-use zones for overlaps and 24-hour coverage
        // Implements Property 6: Time-of-use zone validation
        // Validates: Requirements 5.5, 5.6
        if (isset($data['configuration']['type']) && $data['configuration']['type'] === 'time_of_use') {
            if (isset($data['configuration']['zones']) && is_array($data['configuration']['zones'])) {
                $timeRangeValidator = app(TimeRangeValidator::class);
                $errors = $timeRangeValidator->validate($data['configuration']['zones']);
                
                if (!empty($errors)) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages['data.configuration.zones'] = $error;
                    }
                    throw ValidationException::withMessages($errorMessages);
                }
            }
        }
    }
}
