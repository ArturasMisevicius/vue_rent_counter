<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformNotifications\RelationManagers;

use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PlatformNotificationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Recipients';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['organization:id,name'])->latestSentFirst())
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->placeholder('Platform'),
                TextColumn::make('email')
                    ->label('Recipient Email')
                    ->searchable(),
                TextColumn::make('delivery_status')
                    ->label('Delivery Status')
                    ->badge(),
                IconColumn::make('read_at')
                    ->label('Opened')
                    ->boolean()
                    ->state(fn ($state): bool => filled($state)),
                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->placeholder('Pending'),
            ])
            ->defaultSort('sent_at', 'desc');
    }
}
