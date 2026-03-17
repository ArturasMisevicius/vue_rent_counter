<?php

namespace App\Filament\Actions\Superadmin\Languages;

use App\Models\Language;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteLanguageAction
{
    public function handle(Language $language): void
    {
        if ($language->is_default) {
            throw ValidationException::withMessages([
                'language' => 'The default language cannot be deleted.',
            ]);
        }

        if (User::query()->where('locale', $language->code)->exists()) {
            throw ValidationException::withMessages([
                'language' => 'The selected language is currently assigned to users.',
            ]);
        }

        $language->delete();
    }
}
