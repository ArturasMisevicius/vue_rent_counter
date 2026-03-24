<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationsSummaryAction;
use App\Filament\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Filament\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Filament\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Organization Name')
                    ->url(fn (Organization $record): string => OrganizationResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.email')
                    ->label('Owner Email')
                    ->placeholder(__('superadmin.organizations.empty.owner'))
                    ->searchable(),
                TextColumn::make('currentSubscription.plan')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionPlan $state): string => $state?->label() ?? 'No plan')
                    ->color('primary'),
                TextColumn::make('currentSubscription.status')
                    ->label('Subscription Status')
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionStatus $state): string => $state?->label() ?? 'No subscription')
                    ->color(fn (?SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::ACTIVE => 'success',
                        SubscriptionStatus::EXPIRED => 'danger',
                        SubscriptionStatus::SUSPENDED => 'warning',
                        SubscriptionStatus::CANCELLED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('buildings_count')
                    ->label('Properties')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('tenants_count')
                    ->label('Tenants')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('subscription_status')
                    ->label('Subscription Status')
                    ->placeholder('All Statuses')
                    ->options(SubscriptionStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        return $query->when(
                            filled($status),
                            fn (Builder $query): Builder => $query->whereHas(
                                'currentSubscription',
                                fn (Builder $subscriptionQuery): Builder => $subscriptionQuery->where('status', $status),
                            ),
                        );
                    }),
                SelectFilter::make('plan')
                    ->label('Plan')
                    ->placeholder('All Plans')
                    ->options(SubscriptionPlan::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $plan = $data['value'] ?? null;

                        return $query->when(
                            filled($plan),
                            fn (Builder $query): Builder => $query->whereHas(
                                'currentSubscription',
                                fn (Builder $subscriptionQuery): Builder => $subscriptionQuery->where('plan', $plan),
                            ),
                        );
                    }),
                Filter::make('created_between')
                    ->label('Created')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_to')
                            ->label('Created To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['created_from'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $data['created_from']),
                            )
                            ->when(
                                filled($data['created_to'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $data['created_to']),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View'),
                EditAction::make()
                    ->label('Edit'),
                ActionGroup::make([
                    Action::make('suspendOrganization')
                        ->label('Suspend Organization')
                        ->icon(Heroicon::OutlinedPauseCircle)
                        ->color('danger')
                        ->visible(fn (Organization $record): bool => $record->status->permitsAccess())
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('suspend', $record) ?? false)
                        ->requiresConfirmation()
                        ->modalDescription(fn (Organization $record): string => "Suspend {$record->name} and immediately terminate active sessions.")
                        ->action(function (Organization $record, SuspendOrganizationAction $suspendOrganizationAction): void {
                            $suspendOrganizationAction->handle($record);

                            Notification::make()
                                ->title('Organization suspended')
                                ->success()
                                ->send();
                        }),
                    Action::make('sendNotification')
                        ->label('Send Notification')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->slideOver()
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('update', $record) ?? false)
                        ->schema([
                            TextInput::make('title')
                                ->label('Notification Title')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('body')
                                ->label('Message Body')
                                ->required()
                                ->rows(5),
                            Select::make('severity')
                                ->label('Severity')
                                ->options([
                                    'information' => 'Information',
                                    'warning' => 'Warning',
                                    'critical' => 'Critical',
                                ])
                                ->default('information')
                                ->required(),
                        ])
                        ->action(function (Organization $record, array $data, SendOrganizationNotificationAction $sendOrganizationNotificationAction): void {
                            $sendOrganizationNotificationAction->handle(
                                $record,
                                $data['title'],
                                $data['body'],
                                $data['severity'],
                            );

                            Notification::make()
                                ->title('Notification sent')
                                ->success()
                                ->send();
                        }),
                    Action::make('impersonateAdmin')
                        ->label('Impersonate Admin')
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->visible(fn (Organization $record): bool => $record->status->permitsAccess())
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('impersonate', $record) ?? false)
                        ->requiresConfirmation()
                        ->modalDescription('Switch into the organization primary admin account until you stop impersonating.')
                        ->action(function (Organization $record, StartOrganizationImpersonationAction $startOrganizationImpersonationAction): void {
                            $impersonator = self::currentUser();
                            $admin = self::resolvePrimaryAdmin($record);

                            abort_if(! $impersonator instanceof User, 403);
                            abort_if(! $admin instanceof User, 404, 'No primary admin is available for this organization.');

                            $startOrganizationImpersonationAction->handle($impersonator, $admin);
                        }),
                    Action::make('exportData')
                        ->label('Export Data')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('view', $record) ?? false)
                        ->action(function (Organization $record, ExportOrganizationDataAction $exportOrganizationDataAction) {
                            $path = $exportOrganizationDataAction->handle($record);

                            return response()
                                ->download($path, "{$record->slug}-organization-export.zip")
                                ->deleteFileAfterSend(true);
                        }),
                    DeleteAction::make('deleteOrganization')
                        ->label('Delete Organization'),
                ])
                    ->label('More')
                    ->icon(Heroicon::OutlinedEllipsisHorizontal),
            ])
            ->toolbarActions([
                DeleteBulkAction::make('deleteSelected')
                    ->label('Delete Selected'),
                BulkAction::make('exportSelected')
                    ->label('Export Selected')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->authorize(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->action(function (Collection $records, ExportOrganizationsSummaryAction $exportOrganizationsSummaryAction) {
                        $path = $exportOrganizationsSummaryAction->handle($records);

                        return response()
                            ->download($path, 'organizations-export.csv')
                            ->deleteFileAfterSend(true);
                    }),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->searchPlaceholder('Search by organization name or owner email')
            ->defaultPaginationPageOption(20)
            ->paginationPageOptions([20])
            ->defaultSort('created_at', 'desc');
    }

    private static function resolvePrimaryAdmin(Organization $organization): ?User
    {
        $owner = $organization->owner;

        if ($owner instanceof User && $owner->role === UserRole::ADMIN) {
            return $owner;
        }

        return $organization->users()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'last_login_at',
                'created_at',
                'updated_at',
                'password',
                'remember_token',
            ])
            ->where('role', UserRole::ADMIN)
            ->orderedByName()
            ->first();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
