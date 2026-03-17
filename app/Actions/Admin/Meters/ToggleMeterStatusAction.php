<?php

namespace App\Actions\Admin\Meters;

use App\Enums\MeterStatus;
use App\Models\Meter;

class ToggleMeterStatusAction
{
    public function handle(Meter $meter): Meter
    {
        $meter->update([
            'status' => $meter->status === MeterStatus::ACTIVE
                ? MeterStatus::INACTIVE
                : MeterStatus::ACTIVE,
        ]);

        return $meter->fresh();
    }
}
