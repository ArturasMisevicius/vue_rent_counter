<?php

namespace App\Actions\Superadmin\Languages;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Support\Facades\DB;

class SetDefaultLanguageAction
{
    public function __invoke(Language $language): Language
    {
        return DB::transaction(function () use ($language): Language {
            Language::query()->where('is_default', true)->update([
                'is_default' => false,
            ]);

            $language->forceFill([
                'is_default' => true,
                'status' => LanguageStatus::ACTIVE,
            ])->save();

            return $language->refresh();
        });
    }
}
