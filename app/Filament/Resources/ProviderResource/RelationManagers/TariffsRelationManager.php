<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use App\Enums\TariffType;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

/**
 * Relation manager for provider tariffs.
 *
 * Displays tariffs associated with a provider with:
 * - Read-only view (tariffs managed via TariffResource)
 * - Active status indicators
 * - Tariff type badges
 */
class TariffsRelationManager extends RelationManager
{
    protected static string $relationship = 'tariffs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('tariffs.forms.name'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('configuration.type')
                    ->label(__('tariffs.forms.type'))
                    ->badge()
                    ->color(fn (string $state): string => match (TariffType::tryFrom($state)) {
                        TariffType::FLAT => 'success',
                        TariffType::TIME_OF_USE => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => TariffType::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_from')
                    ->label(__('tariffs.forms.active_from'))
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_until')
                    ->label(__('tariffs.forms.active_until'))
                    ->date()
                    ->sortable()
                    ->placeholder(__('tariffs.forms.no_end_date')),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('tariffs.labels.status'))
                    ->boolean()
                    ->getStateUsing(function ($record): bool {
                        return $record->isActiveOn(now());
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Don't allow creating tariffs from here - they should be created from TariffResource
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.tariffs.edit', ['record' => $record])),
            ])
            ->bulkActions([
                // No bulk actions for tariffs in this context
            ])
            ->defaultSort('active_from', 'desc');
    }
}
