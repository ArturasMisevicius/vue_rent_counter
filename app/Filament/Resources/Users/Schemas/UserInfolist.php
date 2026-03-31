<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Support\Superadmin\Users\UserDossierData;
use App\Models\User;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.resources.users.dossier')
                    ->viewData(fn (User $record): array => app(UserDossierData::class)->for($record)),
            ]);
    }
}
