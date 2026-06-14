<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadSources\Tables;

use App\Filament\Resources\LeadSources\LeadSourceResource;
use App\Models\LeadSource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.lead_sources.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.lead_sources.fields.type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('retention_days')
                    ->label(__('admin.lead_sources.fields.retention_days'))
                    ->sortable(),
                TextColumn::make('imported_at')
                    ->label(__('admin.lead_sources.fields.imported_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->authorize(fn (LeadSource $record): bool => LeadSourceResource::canEdit($record)),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->authorize(fn (LeadSource $record): bool => LeadSourceResource::canDelete($record)),
            ])
            ->defaultSort('name');
    }
}
