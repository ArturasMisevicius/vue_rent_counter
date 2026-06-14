<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadImportBatches\Schemas;

use App\Models\LeadImportBatch;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadImportBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.lead_import_batches.sections.summary'))
                ->schema([
                    TextEntry::make('filename')
                        ->label(__('admin.lead_import_batches.fields.filename')),
                    TextEntry::make('source.name')
                        ->label(__('admin.lead_import_batches.fields.lead_source'))
                        ->placeholder('—'),
                    TextEntry::make('uploader.name')
                        ->label(__('admin.lead_import_batches.fields.uploaded_by'))
                        ->placeholder('—'),
                    TextEntry::make('status')
                        ->label(__('admin.lead_import_batches.fields.status'))
                        ->badge(),
                    TextEntry::make('rows_total')
                        ->label(__('admin.lead_import_batches.fields.rows_total')),
                    TextEntry::make('rows_imported')
                        ->label(__('admin.lead_import_batches.fields.rows_imported')),
                    TextEntry::make('rows_skipped')
                        ->label(__('admin.lead_import_batches.fields.rows_skipped')),
                    TextEntry::make('rows_duplicates')
                        ->label(__('admin.lead_import_batches.fields.rows_duplicates')),
                    TextEntry::make('rows_failed')
                        ->label(__('admin.lead_import_batches.fields.rows_failed')),
                    TextEntry::make('finished_at')
                        ->label(__('admin.lead_import_batches.fields.finished_at'))
                        ->dateTime()
                        ->placeholder('—'),
                ])
                ->columns(3),
            Section::make(__('admin.lead_import_batches.sections.mapping'))
                ->schema([
                    TextEntry::make('mapping_config')
                        ->label(__('admin.lead_import_batches.fields.mapping_config'))
                        ->state(fn (LeadImportBatch $record): string => json_encode($record->mapping_config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}')
                        ->columnSpanFull(),
                    TextEntry::make('error_summary')
                        ->label(__('admin.lead_import_batches.fields.error_summary'))
                        ->state(fn (LeadImportBatch $record): string => json_encode($record->error_summary ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
