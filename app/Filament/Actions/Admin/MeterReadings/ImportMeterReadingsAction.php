<?php

namespace App\Filament\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Models\Meter;

class ImportMeterReadingsAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
    ) {}

    /**
     * @param  list<array{
     *     reading_value: string|int|float,
     *     reading_date: string,
     *     submission_method: string
     * }>  $rows
     * @return array{
     *     valid: list<array{
     *         reading_value: string|int|float,
     *         reading_date: string,
     *         submission_method: string,
     *         status: string,
     *         notes: string|null
     *     }>,
     *     invalid: list<array{
     *         reading_value: string|int|float,
     *         reading_date: string,
     *         submission_method: string,
     *         status: string,
     *         errors: array<string, list<string>>
     *     }>
     * }
     */
    public function handle(Meter $meter, array $rows): array
    {
        $preview = [
            'valid' => [],
            'invalid' => [],
        ];

        foreach ($rows as $row) {
            $validation = $this->validateReadingValue->handle(
                $meter,
                $row['reading_value'],
                $row['reading_date'],
            );

            if ($validation->fails()) {
                $preview['invalid'][] = [
                    ...$row,
                    'status' => $validation->status->value,
                    'errors' => $validation->messages,
                ];

                continue;
            }

            $preview['valid'][] = [
                ...$row,
                'submission_method' => MeterReadingSubmissionMethod::from($row['submission_method'])->value,
                'status' => $validation->status->value,
                'notes' => $validation->notesAsText(),
            ];
        }

        return $preview;
    }
}
