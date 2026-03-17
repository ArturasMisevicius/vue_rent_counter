<?php

namespace App\Actions\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Models\Meter;
use App\Support\Admin\ReadingValidation\ValidateReadingValue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportMeterReadingsAction
{
    public function __construct(
        private readonly ValidateReadingValue $validateReadingValue,
    ) {}

    /**
     * @param  array<int, array{
     *     reading_value: mixed,
     *     reading_date: mixed,
     *     submission_method?: mixed
     * }>  $rows
     * @return array{
     *     valid: array<int, array{row: int, status: string, notes: array<int, string>}>,
     *     invalid: array<int, array{row: int, errors: array<string, array<int, string>}>
     * }
     */
    public function handle(Meter $meter, array $rows): array
    {
        $valid = [];
        $invalid = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, [
                'reading_value' => ['required', 'numeric', 'min:0'],
                'reading_date' => ['required', 'date'],
                'submission_method' => ['nullable', Rule::in(array_map(
                    fn (MeterReadingSubmissionMethod $method): string => $method->value,
                    MeterReadingSubmissionMethod::cases(),
                ))],
            ]);

            if ($validator->fails()) {
                $invalid[] = [
                    'row' => $index + 1,
                    'errors' => $validator->errors()->toArray(),
                ];

                continue;
            }

            $validation = $this->validateReadingValue->handle(
                $meter,
                $row['reading_value'],
                $row['reading_date'],
            );

            if ($validation->fails()) {
                $invalid[] = [
                    'row' => $index + 1,
                    'errors' => $validation->messages,
                ];

                continue;
            }

            $valid[] = [
                'row' => $index + 1,
                'status' => $validation->status->value,
                'notes' => $validation->notes,
            ];
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }
}
