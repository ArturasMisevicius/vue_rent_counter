<?php

namespace App\Filament\Exports;

use App\Models\FrameworkShowcase;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class FrameworkShowcaseExporter extends Exporter
{
    protected static ?string $model = FrameworkShowcase::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title'),
            ExportColumn::make('slug'),
            ExportColumn::make('status'),
            ExportColumn::make('organization.name')->label('Organization'),
            ExportColumn::make('author.name')->label('Author'),
            ExportColumn::make('is_featured')
                ->state(fn (FrameworkShowcase $record): string => $record->is_featured ? 'Yes' : 'No'),
            ExportColumn::make('published_at'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your framework showcase export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
