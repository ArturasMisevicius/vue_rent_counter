<?php

namespace App\Filament\Resources\Providers\Tables;

use App\Filament\Actions\Admin\Providers\DeleteProviderAction;
use App\Filament\Resources\Providers\ProviderResource;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.providers.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider_code')
                    ->label(__('admin.providers.columns.code'))
                    ->fontFamily('mono')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('id', $direction)),
                TextColumn::make('service_type')
                    ->label(__('admin.providers.columns.service_type'))
                    ->state(fn (Provider $record): ?string => $record->service_type?->getLabel())
                    ->badge(),
                TextColumn::make('contact_info.email')
                    ->label(__('admin.providers.columns.email'))
                    ->default(__('admin.providers.empty.contact'))
                    ->toggleable(),
                TextColumn::make('tariffs_count')
                    ->label(__('admin.providers.columns.tariffs_count'))
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
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit')),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->using(fn (Provider $record) => app(DeleteProviderAction::class)->handle($record))
                    ->authorize(fn (Provider $record): bool => ProviderResource::canDelete($record))
                    ->disabled(fn (Provider $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                    ->tooltip(fn (Provider $record): ?string => $record->adminDeletionBlockedReason()),
            ])
            ->defaultSort('name');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
