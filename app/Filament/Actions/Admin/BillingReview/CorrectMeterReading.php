<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Models\MeterReading;
use App\Models\User;

final readonly class CorrectMeterReading
{
    public function __construct(
        private CorrectReading $correctReading,
    ) {}

    /**
     * @param  array{reading_value?: string|int|float, reading_date?: string|null, reason?: string|null, confirm_negative_consumption?: bool}  $data
     */
    public function handle(MeterReading $reading, array $data, ?User $actor = null): MeterReading
    {
        return $this->correctReading->handle($reading, $data, $actor);
    }
}
