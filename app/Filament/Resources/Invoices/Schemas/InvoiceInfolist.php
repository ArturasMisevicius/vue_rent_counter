<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Billing\InvoicePresentationService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.invoices.sections.details'))
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label(__('admin.invoices.fields.invoice_number')),
                        TextEntry::make('tenant.name')
                            ->label(__('admin.invoices.fields.tenant')),
                        TextEntry::make('property.name')
                            ->label(__('admin.invoices.fields.property')),
                        TextEntry::make('property.building.name')
                            ->label(__('admin.invoices.fields.building')),
                        TextEntry::make('billing_period_start')
                            ->label(__('admin.invoices.fields.billing_period_start'))
                            ->date(),
                        TextEntry::make('billing_period_end')
                            ->label(__('admin.invoices.fields.billing_period_end'))
                            ->date(),
                        TextEntry::make('status')
                            ->label(__('admin.invoices.fields.status'))
                            ->badge(),
                        TextEntry::make('due_date')
                            ->label(__('admin.invoices.fields.due_date'))
                            ->date(),
                    ])
                    ->columns(2),
                Section::make(__('admin.invoices.sections.amounts'))
                    ->schema([
                        TextEntry::make('total_amount')
                            ->label(__('admin.invoices.fields.total_amount'))
                            ->state(function ($record): string {
                                $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

                                return (string) $formatter->formatCurrency((float) $record->total_amount, $record->currency);
                            }),
                        TextEntry::make('amount_paid')
                            ->label(__('admin.invoices.fields.amount_paid'))
                            ->state(function ($record): string {
                                $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

                                return (string) $formatter->formatCurrency((float) $record->normalized_paid_amount, $record->currency);
                            }),
                        TextEntry::make('outstanding_amount')
                            ->label(__('admin.invoices.status_summaries.outstanding'))
                            ->state(fn ($record): string => app(InvoicePresentationService::class)->present($record)['outstanding_amount_display']),
                        TextEntry::make('document_path')
                            ->label(__('admin.invoices.fields.document_path'))
                            ->default(__('admin.invoices.empty.document_path')),
                        TextEntry::make('status_summary')
                            ->label(__('admin.invoices.fields.status_summary'))
                            ->state(function ($record): string {
                                if ($record->status === InvoiceStatus::PAID || $record->outstanding_balance <= 0) {
                                    return __('admin.invoices.status_summaries.paid');
                                }

                                if ($record->normalized_paid_amount > 0) {
                                    return __('admin.invoices.status_summaries.partially_paid');
                                }

                                return __('admin.invoices.status_summaries.outstanding');
                            }),
                    ])
                    ->columns(2),
                View::make('filament.resources.invoices.explainability')
                    ->viewData(fn (Invoice $record): array => [
                        'invoice' => $record,
                        'presentation' => app(InvoicePresentationService::class)->present($record),
                    ]),
            ]);
    }
}
