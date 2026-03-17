<?php

namespace App\Filament\Resources\Providers\Tables;

use App\Actions\Admin\Providers\DeleteProviderAction;
use App\Enums\ServiceType;
use App\Filament\Resources\Providers\ProviderResource;
use App\Models\Provider;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.providers.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_type')
                    ->label(__('admin.providers.columns.service_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('admin.providers.types.'.($state->value ?? $state))),
                TextColumn::make('contact_info.email')
                    ->label(__('admin.providers.columns.contact_email'))
                    ->toggleable(),
                TextColumn::make('tariffs_count')
                    ->label(__('admin.providers.columns.tariffs_count'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->label(__('admin.providers.fields.service_type'))
                    ->options(
                        collect(ServiceType::cases())
                            ->mapWithKeys(fn (ServiceType $type): array => [
                                $type->value => __('admin.providers.types.'.$type->value),
                            ])
                            ->all(),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (Provider $record) => app(DeleteProviderAction::class)->handle($record))
                    ->authorize(fn (Provider $record): bool => ProviderResource::canDelete($record)),
            ]);
    }
}
