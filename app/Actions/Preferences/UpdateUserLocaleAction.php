<?php

namespace App\Actions\Preferences;

use App\Enums\LanguageStatus;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UpdateUserLocaleAction
{
    public function handle(User $user, string $locale): void
    {
        if (! $this->isSupportedLocale($locale)) {
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

    private function isSupportedLocale(string $locale): bool
    {
        if (Schema::hasTable('languages') && Language::query()->exists()) {
            return Language::query()
                ->where('code', $locale)
                ->where('status', LanguageStatus::ACTIVE)
                ->exists();
        }

        return array_key_exists($locale, config('tenanto.locales', []));
    }
}
