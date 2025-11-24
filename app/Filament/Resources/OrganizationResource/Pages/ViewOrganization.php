<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use BackedEnum;
use App\Enums\SubscriptionPlanType;
use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Schemas\Schema;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\Section::make(__('organizations.sections.details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label(__('organizations.labels.name')),
                        Infolists\Components\TextEntry::make('slug')->label(__('organizations.labels.slug')),
                        Infolists\Components\TextEntry::make('email')->label(__('organizations.labels.email')),
                        Infolists\Components\TextEntry::make('phone')->label(__('organizations.labels.phone')),
                        Infolists\Components\TextEntry::make('domain')->label(__('organizations.labels.domain')),
                    ])->columns(2),

                Infolists\Components\Section::make(__('organizations.sections.subscription'))
                    ->schema([
                        Infolists\Components\TextEntry::make('plan')
                            ->badge()
                            ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                            ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                                SubscriptionPlanType::BASIC->value => 'gray',
                                SubscriptionPlanType::PROFESSIONAL->value => 'info',
                                SubscriptionPlanType::ENTERPRISE->value => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('max_properties')
                            ->label(__('organizations.labels.max_properties')),
                        Infolists\Components\TextEntry::make('max_users')
                            ->label(__('organizations.labels.max_users')),
                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->dateTime()
                            ->placeholder(__('organizations.labels.not_on_trial')),
                        Infolists\Components\TextEntry::make('subscription_ends_at')
                            ->dateTime()
                            ->color(fn ($record) => $record->subscription_ends_at?->isPast() ? 'danger' : 'success'),
                    ])->columns(3),

                Infolists\Components\Section::make(__('superadmin.dashboard.organizations.title', [], false) ?? 'Usage Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('users_count')
                            ->label(__('organizations.labels.total_users'))
                            ->state(fn ($record) => $record->users()->count()),
                        Infolists\Components\TextEntry::make('properties_count')
                            ->label(__('organizations.labels.total_properties'))
                            ->state(fn ($record) => $record->properties()->count()),
                        Infolists\Components\TextEntry::make('buildings_count')
                            ->label(__('organizations.labels.total_buildings'))
                            ->state(fn ($record) => $record->buildings()->count()),
                        Infolists\Components\TextEntry::make('invoices_count')
                            ->label(__('organizations.labels.total_invoices'))
                            ->state(fn ($record) => $record->invoices()->count()),
                        Infolists\Components\TextEntry::make('remaining_properties')
                            ->label(__('organizations.labels.remaining_properties'))
                            ->state(fn ($record) => $record->getRemainingProperties()),
                        Infolists\Components\TextEntry::make('remaining_users')
                            ->label(__('organizations.labels.remaining_users'))
                            ->state(fn ($record) => $record->getRemainingUsers()),
                    ])->columns(3),

                Infolists\Components\Section::make(__('organizations.sections.regional'))
                    ->schema([
                        Infolists\Components\TextEntry::make('timezone')->label(__('organizations.labels.timezone')),
                        Infolists\Components\TextEntry::make('locale')->label(__('organizations.labels.locale')),
                        Infolists\Components\TextEntry::make('currency')->label(__('organizations.labels.currency')),
                    ])->columns(3),

                Infolists\Components\Section::make(__('organizations.sections.status'))
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label(__('organizations.labels.is_active')),
                        Infolists\Components\TextEntry::make('suspended_at')
                            ->dateTime()
                            ->placeholder(__('organizations.labels.not_suspended')),
                        Infolists\Components\TextEntry::make('suspension_reason')
                            ->placeholder(__('app.common.na')),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('organizations.labels.created_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('users.labels.updated_at', [], false) ?? 'Updated At')
                            ->dateTime(),
                    ])->columns(2),
           ]);
   }
}
