<?php

namespace App\Support\Admin\ReadingValidation;

use App\Enums\MeterReadingValidationStatus;
use App\Models\MeterReading;

class ReadingValidationResult
{
    /**
     * @param  array<string, list<string>>  $messages
     */
    public function __construct(
        public array $messages,
        public MeterReadingValidationStatus $status,
        public ?MeterReading $previousReading = null,
    ) {}

    public function fails(): bool
    {
        return $this->messages !== [];
    }
}
