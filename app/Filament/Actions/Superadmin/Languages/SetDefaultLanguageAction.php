<?php

namespace App\Filament\Actions\Superadmin\Languages;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Support\Facades\DB;

class SetDefaultLanguageAction
{
    public function handle(Language $language): Language
    {
        DB::transaction(function () use ($language): void {
            Language::query()->update([
                'is_default' => false,
            ]);

            $language->update([
                'is_default' => true,
                'status' => LanguageStatus::ACTIVE,
            ]);
        });

        return $language->fresh();
    }
}
