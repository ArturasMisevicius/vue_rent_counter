<?php

namespace App\Actions\Superadmin\Languages;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Validation\ValidationException;

class ToggleLanguageStatusAction
{
    public function __invoke(Language $language, LanguageStatus $status): Language
    {
        if ($status === LanguageStatus::INACTIVE && ! $language->canBeDeactivated()) {
            throw ValidationException::withMessages([
                'status' => 'The default language cannot be deactivated.',
            ]);
        }

        $language->forceFill([
            'status' => $status,
        ])->save();

        return $language->refresh();
    }
}
