<?php

namespace App\Filament\Actions\Admin\Meters;

use App\Models\Meter;

class ToggleMeterStatusAction
{
    public function handle(Meter $meter): Meter
    {
        $meter->update([
            'status' => $meter->status->toggleTarget(),
        ]);

        return $meter->fresh();
    }
}
