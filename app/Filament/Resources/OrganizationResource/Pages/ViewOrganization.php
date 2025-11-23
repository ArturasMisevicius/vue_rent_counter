<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Organization Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('slug'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('domain'),
                    ])->columns(2),

                Infolists\Components\Section::make('Subscription Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('plan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'basic' => 'gray',
                                'professional' => 'info',
                                'enterprise' => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('max_properties')
                            ->label('Max Properties'),
                        Infolists\Components\TextEntry::make('max_users')
                            ->label('Max Users'),
                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->dateTime()
                            ->placeholder('Not on trial'),
                        Infolists\Components\TextEntry::make('subscription_ends_at')
                            ->dateTime()
                            ->color(fn ($record) => $record->subscription_ends_at?->isPast() ? 'danger' : 'success'),
                    ])->columns(3),

                Infolists\Components\Section::make('Usage Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('users_count')
                            ->label('Total Users')
                            ->state(fn ($record) => $record->users()->count()),
                        Infolists\Components\TextEntry::make('properties_count')
                            ->label('Total Properties')
                            ->state(fn ($record) => $record->properties()->count()),
                        Infolists\Components\TextEntry::make('buildings_count')
                            ->label('Total Buildings')
                            ->state(fn ($record) => $record->buildings()->count()),
                        Infolists\Components\TextEntry::make('invoices_count')
                            ->label('Total Invoices')
                            ->state(fn ($record) => $record->invoices()->count()),
                        Infolists\Components\TextEntry::make('remaining_properties')
                            ->label('Remaining Properties')
                            ->state(fn ($record) => $record->getRemainingProperties()),
                        Infolists\Components\TextEntry::make('remaining_users')
                            ->label('Remaining Users')
                            ->state(fn ($record) => $record->getRemainingUsers()),
                    ])->columns(3),

                Infolists\Components\Section::make('Regional Settings')
                    ->schema([
                        Infolists\Components\TextEntry::make('timezone'),
                        Infolists\Components\TextEntry::make('locale'),
                        Infolists\Components\TextEntry::make('currency'),
                    ])->columns(3),

                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        Infolists\Components\TextEntry::make('suspended_at')
                            ->dateTime()
                            ->placeholder('Not suspended'),
                        Infolists\Components\TextEntry::make('suspension_reason')
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
