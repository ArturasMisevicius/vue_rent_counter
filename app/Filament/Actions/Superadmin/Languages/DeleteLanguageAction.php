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
                'language' => __('superadmin.languages_resource.validation.default_cannot_be_deleted'),
            ]);
        }

        if (User::query()->where('locale', $language->code)->exists()) {
            throw ValidationException::withMessages([
                'language' => __('superadmin.languages_resource.validation.assigned_to_users'),
            ]);
        }

        $language->delete();
    }
}
