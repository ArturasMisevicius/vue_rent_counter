<?php

namespace App\Filament\Resources\OrganizationActivityLogResource\Pages;

use App\Filament\Resources\OrganizationActivityLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewOrganizationActivityLog extends ViewRecord
{
    protected static string $resource = OrganizationActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Activity Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Timestamp')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('organization.name')
                            ->label('Organization'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('User'),
                        Infolists\Components\TextEntry::make('action')
                            ->badge(),
                        Infolists\Components\TextEntry::make('resource_type')
                            ->label('Resource Type'),
                        Infolists\Components\TextEntry::make('resource_id')
                            ->label('Resource ID'),
                    ])->columns(2),

                Infolists\Components\Section::make('Request Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address'),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata')
                            ->label('Additional Data')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'N/A')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->metadata)),
            ]);
    }
}
