<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Subscriptions';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('subscriptions_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'payments',
                    'renewals.user:id,name',
                ])
                ->latestFirst())
            ->columns([
                TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expiry Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('property_limit_snapshot')
                    ->label('Property Limit')
                    ->sortable(),
                TextColumn::make('tenant_limit_snapshot')
                    ->label('Tenant Limit')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewHistory')
                    ->label('View History')
                    ->modalHeading('Payment and Renewal History')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Subscription $record): View => view(
                        'filament.resources.organizations.subscription-history',
                        ['subscription' => $record],
                    )),
            ])
            ->recordAction('viewHistory')
            ->defaultSort('expires_at', 'desc');
    }
}
