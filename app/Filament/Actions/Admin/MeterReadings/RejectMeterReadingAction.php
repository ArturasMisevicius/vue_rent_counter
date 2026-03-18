<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\MeterReadings\RejectMeterReadingRequest;
use App\Models\MeterReading;

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
        /** @var RejectMeterReadingRequest $request */
        $request = new RejectMeterReadingRequest;
        $validated = $request->validatePayload($data, auth()->user());
        $reason = (string) $validated['reason'];

        $meterReading->update([
            'validation_status' => MeterReadingValidationStatus::REJECTED,
            'notes' => $this->mergeNotes($meterReading->notes, $reason),
        ]);

        $this->auditLogger->updated($meterReading);

        return $meterReading->fresh();
    }

    private function mergeNotes(?string ...$notes): string
    {
        return collect($notes)
            ->filter(fn (?string $note): bool => filled($note))
            ->implode("\n");
    }
}
