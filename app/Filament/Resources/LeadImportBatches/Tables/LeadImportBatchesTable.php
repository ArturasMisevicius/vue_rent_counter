<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadImportBatches\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadImportBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')
                    ->label(__('admin.lead_import_batches.fields.filename'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source.name')
                    ->label(__('admin.lead_import_batches.fields.lead_source'))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('admin.lead_import_batches.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('rows_total')
                    ->label(__('admin.lead_import_batches.fields.rows_total'))
                    ->sortable(),
                TextColumn::make('rows_imported')
                    ->label(__('admin.lead_import_batches.fields.rows_imported'))
                    ->sortable(),
                TextColumn::make('rows_duplicates')
                    ->label(__('admin.lead_import_batches.fields.rows_duplicates'))
                    ->sortable(),
                TextColumn::make('rows_failed')
                    ->label(__('admin.lead_import_batches.fields.rows_failed'))
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label(__('admin.lead_import_batches.fields.finished_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
