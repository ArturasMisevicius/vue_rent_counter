<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Http\Requests\Admin\MeterReadings\UpdateMeterReadingRequest;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use Illuminate\Validation\ValidationException;

class UpdateMeterReadingAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
    ) {}

    public function handle(MeterReading $meterReading, array $data): MeterReading
    {
        $validated = $this->validate($data);
        $validation = $this->validateReadingValue->handle(
            $meterReading->meter,
            $validated['reading_value'],
            $validated['reading_date'],
            $meterReading->id,
        );

        if ($validation->fails()) {
            throw ValidationException::withMessages($validation->messages);
        }

        $originalValue = (string) $meterReading->reading_value;

        $meterReading->update([
            'reading_value' => $validated['reading_value'],
            'reading_date' => $validated['reading_date'],
            'validation_status' => $validation->status,
            'submission_method' => $validated['submission_method'],
            'notes' => $this->mergeNotes($validated['notes'], $validation->notesAsText()),
        ]);

        if ((string) $validated['reading_value'] !== $originalValue) {
            MeterReadingAudit::query()->create([
                'meter_reading_id' => $meterReading->id,
                'changed_by_user_id' => auth()->id(),
                'old_value' => $originalValue,
                'new_value' => $validated['reading_value'],
                'change_reason' => $validated['notes'] ?: 'Meter reading updated.',
            ]);
        }

        return $meterReading->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     reading_value: string|int|float,
     *     reading_date: string,
     *     submission_method: MeterReadingSubmissionMethod|string,
     *     notes: string|null
     * }
     */
    private function validate(array $data): array
    {
        /** @var UpdateMeterReadingRequest $request */
        $request = new UpdateMeterReadingRequest;
        $validated = $request->validatePayload($data);

        return $validated;
    }

    private function mergeNotes(?string ...$notes): ?string
    {
        $compiledNotes = array_values(array_filter($notes, fn (?string $note): bool => filled($note)));

        if ($compiledNotes === []) {
            return null;
        }

        return implode("\n", $compiledNotes);
    }
}
