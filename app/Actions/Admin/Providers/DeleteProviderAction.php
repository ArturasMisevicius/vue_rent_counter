<?php

namespace App\Actions\Admin\Providers;

use App\Models\Provider;
use Illuminate\Validation\ValidationException;

class DeleteProviderAction
{
    public function handle(Provider $provider): void
    {
        if ($provider->tariffs()->exists() || $provider->serviceConfigurations()->exists()) {
            throw ValidationException::withMessages([
                'provider' => __('admin.providers.messages.delete_blocked'),
            ]);
        }

        $provider->delete();
    }
}
