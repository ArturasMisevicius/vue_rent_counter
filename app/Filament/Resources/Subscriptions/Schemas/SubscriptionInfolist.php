<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.subscriptions_resource.sections.overview'))
                    ->schema([
                        TextEntry::make('organization.name')->label(__('superadmin.subscriptions_resource.fields.organization')),
                        TextEntry::make('plan')->label(__('superadmin.subscriptions_resource.fields.plan'))->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('status')->label(__('superadmin.subscriptions_resource.fields.status'))->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('starts_at')->label(__('superadmin.subscriptions_resource.fields.starts_at'))->dateTime(),
                        TextEntry::make('expires_at')->label(__('superadmin.subscriptions_resource.fields.expires_at'))->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
