<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.tenants.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.tenants.columns.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locale')
                    ->label(__('admin.tenants.columns.locale'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.tenants.columns.status'))
                    ->badge(),
                TextColumn::make('currentPropertyAssignment.property.name')
                    ->label(__('admin.tenants.columns.property'))
                    ->default(__('admin.tenants.empty.property'))
                    ->searchable(),
                TextColumn::make('last_login_at')
                    ->label(__('admin.tenants.columns.last_login_at'))
                    ->state(fn (User $record): string => $record->last_login_at?->format('Y-m-d H:i') ?? __('admin.tenants.empty.never'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.tenants.columns.status'))
                    ->options(UserStatus::options()),
                SelectFilter::make('property_id')
                    ->label(__('admin.tenants.columns.property'))
                    ->query(function ($query, array $data): void {
                        $propertyId = $data['value'] ?? null;

                        if ($propertyId === null || $propertyId === '') {
                            return;
                        }

                        $query->whereHas('currentPropertyAssignment', function ($assignmentQuery) use ($propertyId): void {
                            $assignmentQuery->where('property_id', $propertyId);
                        });
                    })
                    ->options(fn (): array => Property::query()
                        ->select(['id', 'organization_id', 'name'])
                        ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('locale')
                    ->label(__('admin.tenants.columns.locale'))
                    ->options(config('tenanto.locales', [])),
            ])
            ->emptyStateHeading(__('admin.tenants.empty_state.heading'))
            ->emptyStateDescription(__('admin.tenants.empty_state.description'))
            ->emptyStateActions(
                TenantResource::shouldShowBlockedCreateAction('tenants')
                    ? [
                        TenantResource::makeSubscriptionInfoAction(
                            name: 'create',
                            resource: 'tenants',
                            label: __('admin.tenants.empty_state.action'),
                        ),
                    ]
                    : (
                        TenantResource::canCreate()
                            ? [
                                Action::make('createTenant')
                                    ->label(__('admin.tenants.empty_state.action'))
                                    ->url(TenantResource::getUrl('create'))
                                    ->icon('heroicon-m-plus')
                                    ->button(),
                            ]
                            : []
                    ),
            )
            ->recordActions([
                ViewAction::make(),
                ...(
                    TenantResource::shouldInterceptGraceEditAction()
                        ? [
                            TenantResource::makeSubscriptionInfoAction(
                                name: 'edit',
                                resource: 'tenants',
                                label: __('filament-actions::edit.single.label', [
                                    'label' => TenantResource::getModelLabel(),
                                ]),
                            ),
                        ]
                        : (
                            TenantResource::hidesSubscriptionWriteActions()
                                ? []
                                : [
                                    EditAction::make(),
                                ]
                        )
                ),
                ...(
                    TenantResource::canMutateSubscriptionScopedRecords()
                        ? [
                            DeleteAction::make()
                                ->using(fn (User $record) => app(DeleteTenantAction::class)->handle($record))
                                ->authorize(fn (User $record): bool => TenantResource::canDelete($record)),
                        ]
                        : []
                ),
            ])
            ->defaultSort('name');
    }
}
