<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotificationResource\Pages;

use App\Filament\Resources\PlatformNotificationResource;
use App\Models\PlatformNotification;
use App\Services\PlatformNotificationService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Pages\ViewRecord;
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
                Section::make('Notification Details')
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
                                    ->label('Created By'),
                            ]),
                    ]),

                Section::make('Targeting')
                    ->schema([
                        Infolists\Components\TextEntry::make('target_type')
                            ->label('Target Audience')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'all' => 'All Organizations',
                                'plan' => 'Specific Plans',
                                'organization' => 'Individual Organizations',
                                default => $state,
                            }),
                        
                        Infolists\Components\TextEntry::make('target_criteria')
                            ->label('Target Selection')
                            ->formatStateUsing(function (?array $state, PlatformNotification $record): string {
                                if (!$state) {
                                    return 'N/A';
                                }
                                
                                return match ($record->target_type) {
                                    'plan' => implode(', ', $state),
                                    'organization' => \App\Models\Organization::whereIn('id', $state)
                                        ->pluck('name')
                                        ->implode(', '),
                                    default => 'N/A',
                                };
                            })
                            ->visible(fn (PlatformNotification $record) => 
                                in_array($record->target_type, ['plan', 'organization'])),
                    ])
                    ->columns(2),

                Section::make('Scheduling & Delivery')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('scheduled_at')
                                    ->label('Scheduled At')
                                    ->dateTime()
                                    ->placeholder('Not scheduled'),
                                
                                Infolists\Components\TextEntry::make('sent_at')
                                    ->label('Sent At')
                                    ->dateTime()
                                    ->placeholder('Not sent yet'),
                            ]),
                        
                        Infolists\Components\TextEntry::make('failure_reason')
                            ->label('Failure Reason')
                            ->color('danger')
                            ->visible(fn (PlatformNotification $record) => $record->isFailed())
                            ->columnSpanFull(),
                    ]),

                Section::make('Delivery Statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_recipients')
                                    ->label('Total Recipients')
                                    ->state(fn (PlatformNotification $record) => 
                                        $record->getTotalRecipients()),
                                
                                Infolists\Components\TextEntry::make('sent_count')
                                    ->label('Sent')
                                    ->state(fn (PlatformNotification $record) => 
                                        $record->getSentCount())
                                    ->color('success'),
                                
                                Infolists\Components\TextEntry::make('failed_count')
                                    ->label('Failed')
                                    ->state(fn (PlatformNotification $record) => 
                                        $record->getFailedCount())
                                    ->color('danger'),
                                
                                Infolists\Components\TextEntry::make('read_count')
                                    ->label('Read')
                                    ->state(fn (PlatformNotification $record) => 
                                        $record->getReadCount())
                                    ->color('info'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('delivery_rate')
                                    ->label('Delivery Rate')
                                    ->state(fn (PlatformNotification $record) => 
                                        number_format($record->getDeliveryRate(), 1) . '%'),
                                
                                Infolists\Components\TextEntry::make('read_rate')
                                    ->label('Read Rate')
                                    ->state(fn (PlatformNotification $record) => 
                                        number_format($record->getReadRate(), 1) . '%'),
                            ]),
                    ])
                    ->visible(fn (PlatformNotification $record) => $record->isSent()),

                Section::make('Timestamps')
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
