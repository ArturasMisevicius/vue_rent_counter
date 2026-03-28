<?php

namespace App\Filament\Resources\OrganizationUsers\Schemas;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
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
                TextEntry::make('user.name')->label(__('superadmin.users.singular')),
                TextEntry::make('role'),
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
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->visible(fn (OrganizationUser $record): bool => $record->role !== UserRole::MANAGER->value),
                TextEntry::make('joined_at')
                    ->dateTime(),
                TextEntry::make('left_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('inviter.name')
                    ->label(__('admin.organization_users.invited_by'))
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
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
