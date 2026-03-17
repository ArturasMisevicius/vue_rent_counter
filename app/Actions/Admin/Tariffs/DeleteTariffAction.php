<?php

namespace App\Actions\Admin\Tariffs;

use App\Models\Tariff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTariffAction
{
    public function handle(Tariff $tariff): void
    {
        $tariff->loadMissing('serviceConfigurations:id,tariff_id');

        if ($tariff->serviceConfigurations->isNotEmpty()) {
            throw ValidationException::withMessages([
                'tariff' => __('admin.tariffs.messages.delete_blocked'),
            ]);
        }

        DB::transaction(fn () => $tariff->delete());
    }
}
