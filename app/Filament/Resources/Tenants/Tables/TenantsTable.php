<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        self::overrideFilterResetLabel();

        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.tenants.columns.full_name'))
                    ->url(fn (User $record): string => TenantResource::getUrl('view', ['record' => $record]))
                    ->description(fn (User $record): string => $record->email)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.tenants.columns.email'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('currentPropertyAssignment.property.name')
                    ->label(__('admin.tenants.columns.property'))
                    ->state(fn (User $record): string => $record->currentProperty?->name ?? __('admin.tenants.empty.unassigned'))
                    ->url(fn (User $record): ?string => $record->currentProperty !== null
                        ? PropertyResource::getUrl('view', ['record' => $record->currentProperty])
                        : null)
                    ->sortable(),
                TextColumn::make('unit_area')
                    ->label(__('admin.tenants.columns.unit_area'))
                    ->state(fn (User $record): string => $record->currentUnitAreaDisplay()),
                TextColumn::make('phone')
                    ->label(__('admin.tenants.columns.phone'))
                    ->default('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.tenants.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.tenants.columns.date_added'))
                    ->date('F j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->forOrganization((int) $data['value'])
                        : $query),
                SelectFilter::make('property_id')
                    ->label(__('admin.tenants.fields.property'))
                    ->placeholder(__('admin.tenants.filters.all_properties'))
                    ->options(fn (): array => self::propertyFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $propertyId = $data['value'] ?? null;

                        if (blank($propertyId)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'currentPropertyAssignment',
                            fn (Builder $assignmentQuery): Builder => $assignmentQuery->where('property_id', $propertyId),
                        );
                    }),
                SelectFilter::make('status')
                    ->label(__('admin.tenants.columns.status'))
                    ->placeholder(__('admin.tenants.filters.all_statuses'))
                    ->options([
                        UserStatus::ACTIVE->value => UserStatus::ACTIVE->getLabel(),
                        UserStatus::INACTIVE->value => UserStatus::INACTIVE->getLabel(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (blank($status)) {
                            return $query;
                        }

                        return $query->where('status', $status);
                    }),
            ])
            ->emptyStateHeading(__('admin.tenants.empty_state.heading'))
            ->emptyStateDescription(__('admin.tenants.empty_state.description'))
            ->emptyStateActions(
                TenantResource::shouldShowBlockedCreateAction('tenants')
                    ? [
                        TenantResource::makeSubscriptionInfoAction(
                            name: 'create',
                            resource: 'tenants',
                            label: __('admin.tenants.actions.new_tenant'),
                        ),
                    ]
                    : (
                        TenantResource::canCreate()
                            ? [
                                Action::make('createTenant')
                                    ->label(__('admin.tenants.actions.new_tenant'))
                                    ->url(TenantResource::getUrl('create'))
                                    ->icon('heroicon-m-plus')
                                    ->button(),
                            ]
                            : []
                    ),
            )
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                ...(
                    TenantResource::shouldInterceptGraceEditAction()
                        ? [
                            TenantResource::makeSubscriptionInfoAction(
                                name: 'edit',
                                resource: 'tenants',
                                label: __('admin.actions.edit'),
                            ),
                        ]
                        : (
                            TenantResource::hidesSubscriptionWriteActions()
                                ? []
                                : [
                                    EditAction::make()
                                        ->label(__('admin.actions.edit')),
                                ]
                        )
                ),
                ...(
                    TenantResource::canMutateSubscriptionScopedRecords()
                        ? [
                            Action::make('toggleStatus')
                                ->label(fn (User $record): string => $record->status === UserStatus::ACTIVE
                                    ? __('admin.tenants.actions.deactivate')
                                    : __('admin.tenants.actions.reactivate'))
                                ->color(fn (User $record): string => $record->status === UserStatus::ACTIVE ? 'warning' : 'success')
                                ->requiresConfirmation()
                                ->action(function (User $record, ToggleTenantStatusAction $toggleTenantStatusAction): void {
                                    $updatedTenant = $toggleTenantStatusAction->handle($record);

                                    Notification::make()
                                        ->success()
                                        ->title($updatedTenant->status === UserStatus::ACTIVE
                                            ? __('admin.tenants.messages.tenant_reactivated')
                                            : __('admin.tenants.messages.tenant_deactivated'))
                                        ->send();
                                }),
                            DeleteAction::make()
                                ->label(__('admin.actions.delete'))
                                ->using(fn (User $record) => app(DeleteTenantAction::class)->handle($record))
                                ->authorize(fn (User $record): bool => TenantResource::canDelete($record))
                                ->disabled(fn (User $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                                ->tooltip(fn (User $record): ?string => $record->adminDeletionBlockedReason()),
                        ]
                        : []
                ),
            ])
            ->searchPlaceholder(__('admin.tenants.search_placeholder'))
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('name');
    }

    /**
     * @return array<int, string>
     */
    private static function propertyFilterOptions(): array
    {
        $query = Property::query()
            ->select(['id', 'organization_id', 'name'])
            ->orderBy('name')
            ->orderBy('id');

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
        $user = static::currentUser();

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        } elseif (! ($user instanceof User && $user->isSuperadmin())) {
            $query->whereKey(-1);
        }

        return $query->pluck('name', 'id')->all();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => 'Clear Filters',
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => 'Išvalyti filtrus',
        ], 'lt', 'filament-tables');
    }
}
