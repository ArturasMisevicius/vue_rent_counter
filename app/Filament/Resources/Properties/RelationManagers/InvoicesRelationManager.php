<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.invoices');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAdminWorkspaceRelations()->latestBillingFirst())
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('admin.invoices.columns.invoice_number'))
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->label(__('admin.invoices.columns.billing_period'))
                    ->state(fn (Invoice $record): string => collect([
                        $record->billing_period_start?->locale(app()->getLocale())->isoFormat('ll'),
                        $record->billing_period_end?->locale(app()->getLocale())->isoFormat('ll'),
                    ])->filter()->implode(' - ')),
                TextColumn::make('total_amount')
                    ->label(__('admin.invoices.columns.amount'))
                    ->state(function (Invoice $record): string {
                        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

                        return (string) $formatter->formatCurrency((float) $record->total_amount, $record->currency);
                    }),
                TextColumn::make('status')
                    ->label(__('admin.invoices.columns.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.invoices.columns.issued_date'))
                    ->state(fn (Invoice $record): string => $record->created_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—'),
                TextColumn::make('paid_at')
                    ->label(__('admin.invoices.columns.paid_date'))
                    ->state(fn (Invoice $record): string => $record->paid_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record])),
                Action::make('downloadPdf')
                    ->label(__('admin.invoices.actions.download_pdf'))
                    ->action(fn (Invoice $record, InvoicePdfService $invoicePdfService) => $invoicePdfService->streamDownload($record)),
            ])
            ->defaultSort('billing_period_start', 'desc');
    }
}
