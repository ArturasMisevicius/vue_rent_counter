<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use App\Models\Language;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return Language::query()->create([
            ...$data,
            'is_default' => false,
        ]);
    }
}
