<?php

namespace App\Actions\Preferences;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateUserLocaleAction
{
    public function handle(User $user, string $locale): void
    {
        if (! array_key_exists($locale, config('tenanto.locales', []))) {
            throw ValidationException::withMessages([
                'locale' => __('validation.in', ['attribute' => 'locale']),
            ]);
        }

        if ($user->locale !== $locale) {
            $user->forceFill([
                'locale' => $locale,
            ])->save();
        }

        app()->setLocale($locale);
    }
}
