<?php

namespace App\Actions\Superadmin\Languages;

use App\Models\Language;
use Illuminate\Validation\ValidationException;

class DeleteLanguageAction
{
    public function __invoke(Language $language): void
    {
        if (! $language->canBeDeleted()) {
            throw ValidationException::withMessages([
                'language' => 'This language cannot be deleted while it is default or assigned to users.',
            ]);
        }

        $language->delete();
    }
}
