<?php

namespace App\Filament\Resources\Languages\Tables;

use App\Actions\Superadmin\Languages\DeleteLanguageAction;
use App\Actions\Superadmin\Languages\SetDefaultLanguageAction;
use App\Actions\Superadmin\Languages\ToggleLanguageStatusAction;
use App\Enums\LanguageStatus;
use App\Models\Language;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('native_name')
                    ->label('Native name')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        LanguageStatus::ACTIVE->value => 'Active',
                        LanguageStatus::INACTIVE->value => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('setDefault')
                    ->label('Make default')
                    ->requiresConfirmation()
                    ->visible(fn (Language $record): bool => ! $record->is_default)
                    ->action(fn (Language $record) => app(SetDefaultLanguageAction::class)($record)),
                Action::make('activate')
                    ->label('Activate')
                    ->visible(fn (Language $record): bool => $record->status === LanguageStatus::INACTIVE)
                    ->action(fn (Language $record) => app(ToggleLanguageStatusAction::class)($record, LanguageStatus::ACTIVE)),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->disabled(fn (Language $record): bool => ! $record->canBeDeactivated())
                    ->visible(fn (Language $record): bool => $record->status === LanguageStatus::ACTIVE)
                    ->action(fn (Language $record) => app(ToggleLanguageStatusAction::class)($record, LanguageStatus::INACTIVE)),
                DeleteAction::make()
                    ->disabled(fn (Language $record): bool => ! $record->canBeDeleted())
                    ->action(fn (Language $record) => app(DeleteLanguageAction::class)($record)),
            ]);
    }
}
