<?php

namespace App\Filament\Resources\PlatformNotifications\Tables;

use App\Actions\Superadmin\Notifications\SendPlatformNotificationAction;
use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Models\PlatformNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlatformNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('target_scope')
                    ->label('Recipients')
                    ->formatStateUsing(fn (string $state): string => (string) config("tenanto.notifications.target_scopes.{$state}", $state)),
                TextColumn::make('deliveries_count')
                    ->label('Delivered')
                    ->numeric(),
                TextColumn::make('sent_at')
                    ->label('Sent at')
                    ->dateTime()
                    ->placeholder('Draft'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PlatformNotificationStatus::cases())
                        ->mapWithKeys(fn (PlatformNotificationStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('severity')
                    ->options(collect(PlatformNotificationSeverity::cases())
                        ->mapWithKeys(fn (PlatformNotificationSeverity $severity): array => [$severity->value => $severity->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (PlatformNotification $record): bool => $record->canBeSent()),
                Action::make('sendNow')
                    ->label('Send now')
                    ->requiresConfirmation()
                    ->modalHeading('Send platform notification')
                    ->visible(fn (PlatformNotification $record): bool => $record->canBeSent())
                    ->action(fn (PlatformNotification $record) => app(SendPlatformNotificationAction::class)($record)),
            ]);
    }
}
