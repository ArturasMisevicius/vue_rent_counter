<?php

namespace App\Filament\Resources\OrganizationUsers\Schemas;

use App\Enums\UserRole;
use App\Filament\Forms\Components\ManagerPermissionMatrix;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('role')
                    ->options([
                        'viewer' => __('enums.user_role.viewer'),
                        'admin' => __('enums.user_role.admin'),
                        'manager' => __('enums.user_role.manager'),
                        'tenant' => __('enums.user_role.tenant'),
                    ])
                    ->required()
                    ->default('viewer'),
                KeyValue::make('permissions')
                    ->nullable()
                    ->columnSpanFull(),
                ManagerPermissionMatrix::make()
                    ->data(fn ($record): array => [
                        'record' => $record,
                        'organizationId' => $record?->organization_id,
                        'userId' => $record?->user_id,
                    ])
                    ->visible(fn ($record): bool => filled($record) && $record->role === UserRole::MANAGER->value),
                DateTimePicker::make('joined_at')
                    ->required(),
                DateTimePicker::make('left_at'),
                Toggle::make('is_active')
                    ->required(),
                Select::make('invited_by')
                    ->relationship('inviter', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
