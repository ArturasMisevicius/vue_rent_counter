<?php

namespace App\Filament\Resources\Tariffs\Tables;

use App\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Models\Tariff;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TariffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.tariffs.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider.name')
                    ->label(__('admin.tariffs.columns.provider'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('configuration.rate')
                    ->label(__('admin.tariffs.columns.rate'))
                    ->sortable(),
                TextColumn::make('active_from')
                    ->label(__('admin.tariffs.columns.active_from'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider_id')
                    ->relationship('provider', 'name')
                    ->label(__('admin.tariffs.fields.provider')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (Tariff $record) => app(DeleteTariffAction::class)->handle($record))
                    ->authorize(fn (Tariff $record): bool => TariffResource::canDelete($record)),
            ]);
    }
}
