<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Models\BillingRecord;
use App\Models\MeterReading;
use Illuminate\Validation\ValidationException;

class DeleteMeterReadingAction
{
    public function handle(MeterReading $meterReading): void
    {
        if (! $this->canDelete($meterReading)) {
            throw ValidationException::withMessages([
                'meter_reading' => __('admin.meter_readings.messages.delete_blocked_billed'),
            ]);
        }

        $meterReading->delete();
    }

    public function canDelete(MeterReading $meterReading): bool
    {
        return ! BillingRecord::query()
            ->where('meter_reading_start', $meterReading->id)
            ->orWhere('meter_reading_end', $meterReading->id)
            ->exists();
    }
}
