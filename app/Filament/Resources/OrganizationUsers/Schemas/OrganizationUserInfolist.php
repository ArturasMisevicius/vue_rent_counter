<?php

namespace App\Filament\Resources\OrganizationUsers\Schemas;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationUserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('user.name')->label(__('admin.organization_users.fields.name')),
                TextEntry::make('user.email')->label(__('admin.organization_users.fields.email')),
                TextEntry::make('role')
                    ->label(__('admin.organization_users.fields.role'))
                    ->state(fn (OrganizationUser $record): string => $record->roleLabel()),
                TextEntry::make('status')
                    ->label(__('admin.organization_users.fields.status'))
                    ->state(fn (OrganizationUser $record): string => $record->statusLabel())
                    ->badge(),
                TextEntry::make('permissions_preset')
                    ->label(__('admin.organization_users.fields.permissions_preset'))
                    ->state(fn (OrganizationUser $record): string => ManagerPermissionCatalog::presets()[(string) ($record->permissions_preset ?: 'read_only')]['name']
                        ?? __('admin.manager_permissions.presets.custom'))
                    ->badge(),
                Section::make(__('admin.manager_permissions.section'))
                    ->description(__('admin.manager_permissions.description'))
                    ->schema([
                        TextEntry::make('manager_permission_summary')
                            ->hiddenLabel()
                            ->state(fn (OrganizationUser $record): array => static::managerPermissionSummary($record))
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (OrganizationUser $record): bool => $record->role === UserRole::MANAGER->value),
                TextEntry::make('permissions')
                    ->label(__('superadmin.relation_resources.organization_users.fields.permissions'))
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->visible(fn (OrganizationUser $record): bool => $record->role !== UserRole::MANAGER->value),
                TextEntry::make('invited_at')
                    ->label(__('admin.organization_users.fields.invited_at'))
                    ->dateTime(),
                TextEntry::make('accepted_at')
                    ->label(__('admin.organization_users.fields.accepted_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('disabled_at')
                    ->label(__('admin.organization_users.fields.disabled_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('user.last_login_at')
                    ->label(__('admin.organization_users.fields.last_login_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('invitedBy.name')
                    ->label(__('admin.organization_users.fields.invited_by'))
                    ->state(fn (OrganizationUser $record): ?string => $record->invitedBy?->name ?? $record->inviter?->name)
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    /**
     * @return list<string>
     */
    private static function managerPermissionSummary(OrganizationUser $record): array
    {
        $manager = $record->user;
        $organization = $record->organization;

        if (! $manager instanceof User || ! $organization instanceof Organization) {
            return [__('admin.manager_permissions.summary.read_only')];
        }

        return ManagerPermissionCatalog::summaryLines(
            app(ManagerPermissionService::class)->getMatrix($manager, $organization),
        );
    }
}
