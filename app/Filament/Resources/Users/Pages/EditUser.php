<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string
    {
        return sprintf('Edit User: %s (%s)', $this->record->name, $this->record->email);
    }

    public function getSubheading(): ?string
    {
        return $this->record->email;
    }
}
