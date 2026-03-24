<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.users.sections.details'))
                    ->schema([
                        TextInput::make('name')->label(__('superadmin.users.fields.name'))->required()->maxLength(255),
                        TextInput::make('email')->label(__('superadmin.users.fields.email'))->email()->required()->maxLength(255),
                        Select::make('role')
                            ->label(__('superadmin.users.fields.role'))
                            ->options(UserRole::options())
                            ->required(),
                        Select::make('organization_id')
                            ->label(__('superadmin.users.fields.organization'))
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label(__('superadmin.users.fields.status'))
                            ->options(UserStatus::options())
                            ->required(),
                        Select::make('locale')
                            ->label(__('superadmin.users.fields.locale'))
                            ->options(config('tenanto.locales', []))
                            ->required(),
                        TextInput::make('password')
                            ->label(__('superadmin.users.fields.password'))
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ])
                    ->columns(2),
            ]);
    }
}
