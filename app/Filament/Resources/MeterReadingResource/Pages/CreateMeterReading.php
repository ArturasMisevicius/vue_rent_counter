<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Models\Meter;
use App\Services\MeterReadingService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMeterReading extends CreateRecord
{
    protected static string $resource = MeterReadingResource::class;

    /**
     * Mutate form data before creating the record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tenant_id from session
        $data['tenant_id'] = session('tenant_id');
        
        // Set entered_by to current user
        $data['entered_by'] = auth()->id();
        
        return $data;
    }

    /**
     * Validate the form data before creating.
     * Integrates validation rules from StoreMeterReadingRequest.
     */
    protected function beforeValidate(): void
    {
        $data = $this->form->getState();
        
        // Validate monotonicity (from StoreMeterReadingRequest)
        if (isset($data['meter_id']) && isset($data['value'])) {
            $meter = Meter::find($data['meter_id']);
            
            if ($meter) {
                $service = app(MeterReadingService::class);
                $previousReading = $service->getPreviousReading($meter, $data['zone'] ?? null);
                
                if ($previousReading && $data['value'] < $previousReading->value) {
                    throw ValidationException::withMessages([
                        'data.value' => "Reading cannot be lower than previous reading ({$previousReading->value})",
                    ]);
                }
            }
        }
        
        // Validate zone support (from StoreMeterReadingRequest)
        if (isset($data['meter_id'])) {
            $meter = Meter::find($data['meter_id']);
            
            if ($meter) {
                $zone = $data['zone'] ?? null;
                
                if ($zone && !$meter->supports_zones) {
                    throw ValidationException::withMessages([
                        'data.zone' => 'This meter does not support zone-based readings',
                    ]);
                }
                
                if (!$zone && $meter->supports_zones) {
                    throw ValidationException::withMessages([
                        'data.zone' => 'Zone is required for meters that support multiple zones',
                    ]);
                }
            }
        }
    }
}
