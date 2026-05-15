<?php

namespace App\Filament\Resources\OrganizationUsers\Schemas;

use App\Enums\UserRole;
use App\Filament\Forms\Components\ManagerPermissionMatrix;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationUserForm
{
    public static function configure(Schema $schema): Schema
    {
        $canEditMembershipDetails = self::canEditMembershipDetails();

        return $schema
            ->components([
                Select::make('organization_id')
                    ->label(__('superadmin.relation_resources.organization_users.fields.organization'))
                    ->relationship('organization', 'name')
                    ->required()
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
                Select::make('user_id')
                    ->label(__('superadmin.relation_resources.organization_users.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
                Select::make('role')
                    ->label(__('superadmin.relation_resources.organization_users.fields.role'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.organization_users.roles', [
                        'viewer',
                        'admin',
                        'manager',
                        'tenant',
                    ]))
                    ->required()
                    ->default('viewer')
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
                KeyValue::make('permissions')
                    ->label(__('superadmin.relation_resources.organization_users.fields.permissions'))
                    ->nullable()
                    ->columnSpanFull()
                    ->visible($canEditMembershipDetails),
                ManagerPermissionMatrix::make()
                    ->data(fn ($record): array => [
                        'record' => $record,
                        'organizationId' => $record?->organization_id,
                        'userId' => $record?->user_id,
                    ])
                    ->visible(fn ($record): bool => filled($record) && $record->role === UserRole::MANAGER->value),
                DateTimePicker::make('joined_at')
                    ->label(__('superadmin.relation_resources.organization_users.fields.joined_at'))
                    ->required()
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
                DateTimePicker::make('left_at')
                    ->label(__('superadmin.relation_resources.organization_users.fields.left_at'))
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
                Toggle::make('is_active')
                    ->label(__('superadmin.relation_resources.organization_users.fields.is_active'))
                    ->required()
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
                Select::make('invited_by')
                    ->label(__('superadmin.relation_resources.organization_users.fields.inviter'))
                    ->relationship('inviter', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(! $canEditMembershipDetails)
                    ->dehydrated($canEditMembershipDetails),
            ]);
    }

    private static function canEditMembershipDetails(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
