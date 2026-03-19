<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MeterReading;
use App\Models\OrganizationActivityLog;

class ValidateMeterReadingAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(MeterReading $meterReading): MeterReading
    {
        $previousStatus = $meterReading->validation_status;

        $validation = $this->validateReadingValue->handle(
            $meterReading->meter,
            $meterReading->reading_value,
            $meterReading->reading_date->toDateString(),
            $meterReading->id,
        );

        $validationStatus = MeterReadingValidationStatus::VALID;

        $notes = collect([
            $meterReading->notes,
            $validation->notesAsText(),
        ])->filter(static fn (?string $note): bool => filled($note))
            ->implode("\n");

        $meterReading->update([
            'validation_status' => $validationStatus,
            'notes' => $validation->fails()
                ? collect($validation->messages)
                    ->flatten()
                    ->prepend($notes)
                    ->filter(static fn (?string $note): bool => filled($note))
                    ->implode("\n")
                : $notes,
        ]);

        $this->logValidationActivity($meterReading, 'meter_reading.validate', [
            'reading_id' => (int) $meterReading->getKey(),
            'previous_status' => is_string($previousStatus) ? $previousStatus : $previousStatus->value,
            'new_status' => $validationStatus->value,
            'validation_result' => $validation->fails() ? 'warn' : 'pass',
        ]);

        $this->auditLogger->updated($meterReading);

        return $meterReading->fresh();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function logValidationActivity(MeterReading $meterReading, string $action, array $metadata): void
    {
        $actor = auth()->user();

        if ($actor === null) {
            return;
        }

        OrganizationActivityLog::query()->create([
            'organization_id' => $meterReading->organization_id,
            'user_id' => $actor->getKey(),
            'action' => $action,
            'resource_type' => MeterReading::class,
            'resource_id' => $meterReading->getKey(),
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
