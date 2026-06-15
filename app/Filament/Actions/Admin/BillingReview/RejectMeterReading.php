<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Models\MeterReading;
use App\Models\User;

final readonly class RejectMeterReading
{
    public function __construct(
        private RejectReading $rejectReading,
    ) {}

    public function handle(MeterReading $reading, string $reason, ?User $actor = null): MeterReading
    {
        return $this->rejectReading->handle($reading, $reason, $actor);
    }
}
