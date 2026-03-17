<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization overview')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Organization'),
                        TextEntry::make('slug')
                            ->label('Slug'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                        TextEntry::make('owner.name')
                            ->label('Owner')
                            ->placeholder('Invitation pending'),
                        TextEntry::make('currentSubscription.plan_name_snapshot')
                            ->label('Plan')
                            ->placeholder('No subscription'),
                        TextEntry::make('currentSubscription.expires_at')
                            ->label('Renews')
                            ->date('M j, Y')
                            ->placeholder('No renewal date'),
                    ])
                    ->columns(2),
            ]);
    }
}
