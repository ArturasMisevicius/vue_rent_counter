<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

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
                Infolists\Components\Section::make('Organization')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.organization_name')
                            ->label('Organization Name'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Contact Name'),
                    ])->columns(3),

                Infolists\Components\Section::make('Subscription Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('plan_type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'basic' => 'gray',
                                'professional' => 'info',
                                'enterprise' => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'expired' => 'danger',
                                'suspended' => 'warning',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('starts_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('expires_at')
                            ->dateTime()
                            ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('days_until_expiry')
                            ->label('Days Until Expiry')
                            ->state(fn ($record) => $record->daysUntilExpiry())
                            ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 14 ? 'warning' : 'success')),
                    ])->columns(3),

                Infolists\Components\Section::make('Limits')
                    ->schema([
                        Infolists\Components\TextEntry::make('max_properties')
                            ->label('Max Properties'),
                        Infolists\Components\TextEntry::make('max_tenants')
                            ->label('Max Tenants'),
                    ])->columns(2),

                Infolists\Components\Section::make('Usage Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('properties_used')
                            ->label('Properties Used')
                            ->state(fn ($record) => $record->user->properties()->withoutGlobalScopes()->count()),
                        Infolists\Components\TextEntry::make('properties_remaining')
                            ->label('Properties Remaining')
                            ->state(fn ($record) => max(0, $record->max_properties - $record->user->properties()->withoutGlobalScopes()->count())),
                        Infolists\Components\TextEntry::make('tenants_used')
                            ->label('Tenants Used')
                            ->state(fn ($record) => $record->user->childUsers()->count()),
                        Infolists\Components\TextEntry::make('tenants_remaining')
                            ->label('Tenants Remaining')
                            ->state(fn ($record) => max(0, $record->max_tenants - $record->user->childUsers()->count())),
                    ])->columns(4),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
