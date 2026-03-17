<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
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
                    ->options(UserStatus::options()),
                SelectFilter::make('locale')
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
