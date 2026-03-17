<?php

namespace App\Filament\Actions\Admin\Meters;

use App\Models\Meter;
use Illuminate\Validation\ValidationException;

class DeleteMeterAction
{
    public function handle(Meter $meter): void
    {
        if ($meter->readings()->exists()) {
            throw ValidationException::withMessages([
                'meter' => __('admin.meters.messages.delete_blocked'),
            ]);
        }

        $meter->delete();
    }
}
