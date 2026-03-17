<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Actions\Admin\Tenants\DeleteTenantAction;
use App\Enums\UserStatus;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\User;
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
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst((string) ($state->value ?? $state))),
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
                    ->options([
                        UserStatus::ACTIVE->value => 'Active',
                        UserStatus::INACTIVE->value => 'Inactive',
                        UserStatus::SUSPENDED->value => 'Suspended',
                    ]),
                SelectFilter::make('locale')
                    ->options(config('tenanto.locales', [])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (User $record) => app(DeleteTenantAction::class)->handle($record))
                    ->authorize(fn (User $record): bool => TenantResource::canDelete($record)),
            ])
            ->defaultSort('name');
    }
}
