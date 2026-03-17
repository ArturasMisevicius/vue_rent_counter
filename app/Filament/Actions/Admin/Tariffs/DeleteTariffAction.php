<?php

namespace App\Filament\Actions\Admin\Tariffs;

use App\Models\Tariff;
use Illuminate\Validation\ValidationException;

class DeleteTariffAction
{
    public function handle(Tariff $tariff): void
    {
        if ($tariff->serviceConfigurations()->exists()) {
            throw ValidationException::withMessages([
                'tariff' => __('admin.tariffs.messages.delete_blocked'),
            ]);
        }

        $tariff->delete();
    }
}
