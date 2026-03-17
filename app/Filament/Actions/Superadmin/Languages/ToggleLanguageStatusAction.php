<?php

namespace App\Filament\Actions\Superadmin\Languages;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Validation\ValidationException;

class ToggleLanguageStatusAction
{
    public function handle(Language $language): Language
    {
        if ($language->is_default) {
            throw ValidationException::withMessages([
                'language' => 'The default language must remain active.',
            ]);
        }

        $language->update([
            'status' => $language->status === LanguageStatus::ACTIVE
                ? LanguageStatus::INACTIVE
                : LanguageStatus::ACTIVE,
        ]);

        return $language->fresh();
    }
}
