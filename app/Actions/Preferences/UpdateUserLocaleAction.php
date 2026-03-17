<?php

namespace App\Actions\Preferences;

use App\Models\User;

class UpdateUserLocaleAction
{
    public function handle(User $user, string $locale): void
    {
        if ($user->locale !== $locale) {
            $user->forceFill([
                'locale' => $locale,
            ])->save();
        }

        app()->setLocale($locale);
    }
}
