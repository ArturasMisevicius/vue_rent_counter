<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Subscriptions\CreateOrganizationSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpdateOrganizationSubscriptionAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

        if ($count !== null) {
            return (string) min(1, (int) $count);
        }

        return $ownerRecord->currentSubscription()->exists() ? '1' : '0';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->whereKey($this->currentSubscriptionKey())
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
            ->headerActions([
                CreateAction::make()
                    ->label(__('superadmin.subscriptions_resource.actions.new'))
                    ->authorize(function (): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('create', Subscription::class);
                    })
                    ->visible(fn (): bool => ! $this->hasCurrentSubscription())
                    ->form($this->subscriptionFormSchema())
                    ->using(fn (array $data): Subscription => app(CreateOrganizationSubscriptionAction::class)->handle($this->getOwnerRecord(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('superadmin.subscriptions_resource.actions.edit'))
                    ->authorize(function (Subscription $record): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('update', $record);
                    })
                    ->form($this->subscriptionFormSchema())
                    ->using(fn (Subscription $record, array $data): Subscription => app(UpdateOrganizationSubscriptionAction::class)->handle($record, $data)),
                Action::make('viewHistory')
                    ->label(__('superadmin.organizations.relations.subscriptions.actions.view_history'))
                    ->modalHeading(__('superadmin.organizations.relations.subscriptions.modals.history_heading'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('superadmin.organizations.relations.subscriptions.actions.close'))
                    ->modalContent(fn (Subscription $record): View => view(
                        'filament.resources.organizations.subscription-history',
                        ['subscription' => $record],
                    )),
                Action::make('openSubscription')
                    ->label(__('superadmin.subscriptions_resource.actions.view'))
                    ->url(fn (Subscription $record): string => SubscriptionResource::getUrl('view', ['record' => $record])),
            ])
            ->recordAction('viewHistory')
            ->defaultSort('expires_at', 'desc');
    }

    private function hasCurrentSubscription(): bool
    {
        return $this->currentSubscriptionKey() !== 0;
    }

    private function currentSubscriptionKey(): int
    {
        return (int) ($this->getOwnerRecord()->currentSubscription()->select('id')->value('id') ?? 0);
    }

    /**
     * @return array<int, Component>
     */
    private function subscriptionFormSchema(): array
    {
        return [
            Select::make('plan')
                ->label(__('superadmin.subscriptions_resource.fields.plan'))
                ->options(SubscriptionPlan::options())
                ->required(),
            Select::make('status')
                ->label(__('superadmin.subscriptions_resource.fields.status'))
                ->options(SubscriptionStatus::options())
                ->required(),
            DateTimePicker::make('starts_at')
                ->label(__('superadmin.subscriptions_resource.fields.starts_at'))
                ->required(),
            DateTimePicker::make('expires_at')
                ->label(__('superadmin.subscriptions_resource.fields.expires_at'))
                ->required(),
        ];
    }
}
