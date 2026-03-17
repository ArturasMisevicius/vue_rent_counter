<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data)->save();

        return $record;
    }
}
