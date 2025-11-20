<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TariffsRelationManager extends RelationManager
{
    protected static string $relationship = 'tariffs';

    public function form(Form $form): Form
    {
        return $form
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
                    ->label('Tariff Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('configuration.type')
                    ->label('Tariff Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'flat' => 'success',
                        'time_of_use' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'flat' => 'Flat Rate',
                        'time_of_use' => 'Time of Use',
                        default => $state,
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_from')
                    ->label('Active From')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_until')
                    ->label('Active Until')
                    ->date()
                    ->sortable()
                    ->placeholder('No end date'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
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
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.tariffs.edit', ['record' => $record])),
            ])
            ->bulkActions([
                // No bulk actions for tariffs in this context
            ])
            ->defaultSort('active_from', 'desc');
    }
}
