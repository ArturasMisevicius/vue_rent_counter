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
        return __('superadmin.organizations.relations.subscriptions.title');
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
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.plan'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('status')
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('starts_at')
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.start_date'))
                    ->state(fn ($record): string => $record->starts_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.expiry_date'))
                    ->state(fn ($record): string => $record->expires_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—')
                    ->sortable(),
                TextColumn::make('property_limit_snapshot')
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.property_limit'))
                    ->sortable(),
                TextColumn::make('tenant_limit_snapshot')
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.tenant_limit'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.organizations.relations.subscriptions.columns.date_created'))
                    ->state(fn ($record): string => $record->created_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewHistory')
                    ->label(__('superadmin.organizations.relations.subscriptions.actions.view_history'))
                    ->modalHeading(__('superadmin.organizations.relations.subscriptions.modals.history_heading'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('superadmin.organizations.relations.subscriptions.actions.close'))
                    ->modalContent(fn (Subscription $record): View => view(
                        'filament.resources.organizations.subscription-history',
                        ['subscription' => $record],
                    )),
            ])
            ->recordAction('viewHistory')
            ->defaultSort('expires_at', 'desc');
    }
}
