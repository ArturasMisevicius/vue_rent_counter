<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\MeterReadings\RejectMeterReadingRequest;
use App\Models\MeterReading;
use App\Models\OrganizationActivityLog;

class RejectMeterReadingAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(MeterReading $meterReading, array $data): MeterReading
    {
        $previousStatus = $meterReading->validation_status;

        /** @var RejectMeterReadingRequest $request */
        $request = new RejectMeterReadingRequest;
        $validated = $request->validatePayload($data, auth()->user());
        $reason = (string) $validated['reason'];

        $meterReading->update([
            'validation_status' => MeterReadingValidationStatus::REJECTED,
            'notes' => $this->mergeNotes($meterReading->notes, $reason),
        ]);

        $this->logValidationActivity($meterReading, $reason, $previousStatus);

        $this->auditLogger->updated($meterReading);

        return $meterReading->fresh();
    }

    private function logValidationActivity(
        MeterReading $meterReading,
        string $reason,
        MeterReadingValidationStatus|string|null $previousStatus,
    ): void {
        $actor = auth()->user();

        if ($actor === null) {
            return;
        }

        OrganizationActivityLog::query()->create([
            'organization_id' => $meterReading->organization_id,
            'user_id' => $actor->getKey(),
            'action' => 'meter_reading.reject',
            'resource_type' => MeterReading::class,
            'resource_id' => $meterReading->getKey(),
            'metadata' => [
                'reading_id' => (int) $meterReading->getKey(),
                'reason' => $reason,
                'previous_status' => is_string($previousStatus)
                    ? $previousStatus
                    : $previousStatus?->value,
                    : MeterReadingValidationStatus::PENDING->value,
                'new_status' => MeterReadingValidationStatus::REJECTED->value,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function mergeNotes(?string ...$notes): string
    {
        return collect($notes)
            ->filter(fn (?string $note): bool => filled($note))
            ->implode("\n");
    }
}
