<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Models\MeterReading;
use App\Models\User;

final readonly class VoidMeterReading
{
    public function __construct(
        private VoidReading $voidReading,
    ) {}

    public function handle(MeterReading $reading, string $reason, ?User $actor = null): MeterReading
    {
        return $this->voidReading->handle($reading, $reason, $actor);
    }
}
