<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.users.sections.overview'))
                    ->schema([
                        TextEntry::make('name')->label(__('superadmin.users.fields.name')),
                        TextEntry::make('email')->label(__('superadmin.users.fields.email')),
                        TextEntry::make('role')->label(__('superadmin.users.fields.role'))->badge(),
                        TextEntry::make('status')->label(__('superadmin.users.fields.status'))->badge(),
                        TextEntry::make('organization.name')
                            ->label(__('superadmin.users.fields.organization'))
                            ->default(__('superadmin.users.placeholders.platform_user')),
                    ])
                    ->columns(2),
            ]);
    }
}
