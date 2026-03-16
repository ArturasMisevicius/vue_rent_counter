<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\InvoiceResource\Pages;

use App\Filament\Tenant\Resources\InvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

final class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label(__('app.actions.download_pdf'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('invoices.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->status !== \App\Enums\InvoiceStatus::DRAFT),
        ];
    }
}