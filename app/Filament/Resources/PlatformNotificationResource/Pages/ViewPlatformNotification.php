<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\Pages;

use App\Filament\Resources\PlatformNotificationResource;
use App\Models\Organization;
use App\Models\PlatformNotification;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewPlatformNotification extends ViewRecord
{
    protected static string $resource = PlatformNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (PlatformNotification $record) => $record->isDraft()),
            Actions\DeleteAction::make()
                ->visible(fn (PlatformNotification $record) => $record->isDraft()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('platform_notifications.headings.notification_details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('message')
                            ->html()
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'scheduled' => 'warning',
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('creator.name')
                                    ->label(__('platform_notifications.labels.created_by')),
                            ]),
                    ]),

                Section::make(__('platform_notifications.headings.targeting'))
                    ->schema([
                        Infolists\Components\TextEntry::make('target_type')
                            ->label(__('platform_notifications.labels.target_audience'))
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'all' => __('platform_notifications.values.target_type.all'),
                                'plan' => __('platform_notifications.values.target_type.plan'),
                                'organization' => __('platform_notifications.values.target_type.organization'),
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('target_criteria')
                            ->label(__('platform_notifications.labels.target_selection'))
                            ->formatStateUsing(function (?array $state, PlatformNotification $record): string {
                                if (! $state) {
                                    return __('platform_notifications.placeholders.not_available');
                                }

                                return match ($record->target_type) {
                                    'plan' => implode(', ', $state),
                                    'organization' => Organization::whereIn('id', $state)
                                        ->pluck('name')
                                        ->implode(', '),
                                    default => __('platform_notifications.placeholders.not_available'),
                                };
                            })
                            ->visible(fn (PlatformNotification $record) => in_array($record->target_type, ['plan', 'organization'])),
                    ])
                    ->columns(2),

                Section::make(__('platform_notifications.headings.scheduling_and_delivery'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label(__('platform_notifications.labels.scheduled_at'))
                                    ->dateTime()
                                    ->placeholder(__('platform_notifications.placeholders.not_scheduled')),

                                Infolists\Components\TextEntry::make('sent_at')
                                    ->label(__('platform_notifications.labels.sent_at'))
                                    ->dateTime()
                                    ->placeholder(__('platform_notifications.placeholders.not_sent_yet')),
                            ]),

                        Infolists\Components\TextEntry::make('failure_reason')
                            ->label(__('platform_notifications.labels.failure_reason'))
                            ->color('danger')
                            ->visible(fn (PlatformNotification $record) => $record->isFailed())
                            ->columnSpanFull(),
                    ]),

                Section::make(__('platform_notifications.headings.delivery_statistics'))
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_recipients')
                                    ->label(__('platform_notifications.labels.total_recipients'))
                                    ->state(fn (PlatformNotification $record) => $record->getTotalRecipients()),

                                Infolists\Components\TextEntry::make('sent_count')
                                    ->label(__('platform_notifications.labels.sent'))
                                    ->state(fn (PlatformNotification $record) => $record->getSentCount())
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('failed_count')
                                    ->label(__('platform_notifications.labels.failed'))
                                    ->state(fn (PlatformNotification $record) => $record->getFailedCount())
                                    ->color('danger'),

                                Infolists\Components\TextEntry::make('read_count')
                                    ->label(__('platform_notifications.labels.read'))
                                    ->state(fn (PlatformNotification $record) => $record->getReadCount())
                                    ->color('info'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('delivery_rate')
                                    ->label(__('platform_notifications.labels.delivery_rate'))
                                    ->state(fn (PlatformNotification $record) => number_format($record->getDeliveryRate(), 1).'%'),

                                Infolists\Components\TextEntry::make('read_rate')
                                    ->label(__('platform_notifications.labels.read_percent'))
                                    ->state(fn (PlatformNotification $record) => number_format($record->getReadRate(), 1).'%'),
                            ]),
                    ])
                    ->visible(fn (PlatformNotification $record) => $record->isSent()),

                Section::make(__('platform_notifications.headings.timestamps'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime(),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
