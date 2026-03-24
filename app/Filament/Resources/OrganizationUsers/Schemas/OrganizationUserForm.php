<?php

namespace App\Filament\Resources\OrganizationUsers\Schemas;

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
                        'viewer' => 'Viewer',
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'tenant' => 'Tenant',
                    ])
                    ->required()
                    ->default('viewer'),
                KeyValue::make('permissions')
                    ->nullable()
                    ->columnSpanFull(),
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
