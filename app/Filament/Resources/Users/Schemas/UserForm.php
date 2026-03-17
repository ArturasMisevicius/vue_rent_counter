<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Superadmin\Users\StoreUserRequest;
use App\Http\Requests\Superadmin\Users\UpdateUserRequest;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->rules(fn (string $operation, ?User $record): array => $operation === 'create'
                                ? StoreUserRequest::ruleset()['name']
                                : UpdateUserRequest::ruleset($record)['name']),
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->rules(fn (string $operation, ?User $record): array => $operation === 'create'
                                ? StoreUserRequest::ruleset()['email']
                                : UpdateUserRequest::ruleset($record)['email']),
                        Select::make('role')
                            ->label('Role')
                            ->options(collect(UserRole::cases())
                                ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                                ->all())
                            ->required()
                            ->live()
                            ->rules(fn (string $operation, ?User $record): array => $operation === 'create'
                                ? StoreUserRequest::ruleset()['role']
                                : UpdateUserRequest::ruleset($record)['role']),
                        Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name', fn (Builder $query): Builder => $query
                                ->select([
                                    'id',
                                    'name',
                                ]))
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get): bool => $get('role') !== UserRole::SUPERADMIN->value)
                            ->helperText('Required for all non-superadmin users.')
                            ->rules(fn (string $operation, ?User $record): array => $operation === 'create'
                                ? StoreUserRequest::ruleset()['organization_id']
                                : UpdateUserRequest::ruleset($record)['organization_id']),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                UserStatus::ACTIVE->value => 'Active',
                                UserStatus::INACTIVE->value => 'Inactive',
                                UserStatus::SUSPENDED->value => 'Suspended',
                            ])
                            ->default(UserStatus::ACTIVE->value)
                            ->required()
                            ->rules(fn (string $operation, ?User $record): array => $operation === 'create'
                                ? StoreUserRequest::ruleset()['status']
                                : UpdateUserRequest::ruleset($record)['status']),
                    ])
                    ->columns(2),
            ]);
    }
}
