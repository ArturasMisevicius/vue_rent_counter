<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\OrganizationStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.organizations.sections.profile'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('superadmin.organizations.columns.name')),
                        TextEntry::make('slug')
                            ->label(__('superadmin.organizations.columns.slug')),
                        TextEntry::make('status')
                            ->label(__('superadmin.organizations.columns.status'))
                            ->badge()
                            ->color(fn (OrganizationStatus $state): string => $state === OrganizationStatus::ACTIVE ? 'success' : 'danger')
                            ->formatStateUsing(fn (OrganizationStatus $state): string => match ($state) {
                                OrganizationStatus::ACTIVE => __('superadmin.organizations.status.active'),
                                OrganizationStatus::SUSPENDED => __('superadmin.organizations.status.suspended'),
                            }),
                        TextEntry::make('owner.name')
                            ->label(__('superadmin.organizations.columns.owner'))
                            ->default(__('superadmin.organizations.empty.owner')),
                        TextEntry::make('owner.email')
                            ->label(__('superadmin.organizations.columns.owner_email'))
                            ->default(__('superadmin.organizations.empty.owner')),
                    ])
                    ->columns(2),
                Section::make(__('superadmin.organizations.sections.activity'))
                    ->schema([
                        TextEntry::make('users_count')
                            ->label(__('superadmin.organizations.columns.users_count')),
                        TextEntry::make('properties_count')
                            ->label(__('superadmin.organizations.columns.properties_count')),
                        TextEntry::make('subscriptions_count')
                            ->label(__('superadmin.organizations.columns.subscriptions_count')),
                        TextEntry::make('created_at')
                            ->label(__('superadmin.organizations.columns.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('superadmin.organizations.columns.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
