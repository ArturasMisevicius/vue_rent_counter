<?php

namespace App\Actions\Admin\Providers;

use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteProviderAction
{
    public function handle(Provider $provider): void
    {
        $provider->loadMissing(['tariffs:id,provider_id', 'serviceConfigurations:id,provider_id']);

        if ($provider->tariffs->isNotEmpty() || $provider->serviceConfigurations->isNotEmpty()) {
            throw ValidationException::withMessages([
                'provider' => __('admin.providers.messages.delete_blocked'),
            ]);
        }

        DB::transaction(fn () => $provider->delete());
    }
}
