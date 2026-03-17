<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
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
                Section::make('User Details')
                    ->schema([
                        TextInput::make('name')->label('Name')->required()->maxLength(255),
                        TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                        Select::make('role')
                            ->label('Role')
                            ->options(UserRole::options())
                            ->required(),
                        Select::make('organization_id')
                            ->label('Organization')
                            ->options(Organization::query()->select(['id', 'name'])->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Status')
                            ->options(UserStatus::options())
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
