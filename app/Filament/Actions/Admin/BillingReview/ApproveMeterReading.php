<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Models\MeterReading;
use App\Models\User;

final readonly class ApproveMeterReading
{
    public function __construct(
        private ApproveReading $approveReading,
    ) {}

    public function handle(MeterReading $reading, ?User $actor = null, bool $confirmNegativeConsumption = false): MeterReading
    {
        return $this->approveReading->handle($reading, $actor, $confirmNegativeConsumption);
    }
}
