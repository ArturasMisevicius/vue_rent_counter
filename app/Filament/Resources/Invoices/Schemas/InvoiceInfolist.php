<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
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
                            ->formatStateUsing(fn ($state): string => __('admin.invoices.statuses.'.($state->value ?? $state))),
                        TextEntry::make('due_date')
                            ->label(__('admin.invoices.fields.due_date'))
                            ->date(),
                    ])
                    ->columns(2),
                Section::make(__('admin.invoices.sections.amounts'))
                    ->schema([
                        TextEntry::make('total_amount')
                            ->label(__('admin.invoices.fields.total_amount'))
                            ->state(fn ($record): string => sprintf('%s %s', $record->currency, number_format((float) $record->total_amount, 2))),
                        TextEntry::make('amount_paid')
                            ->label(__('admin.invoices.fields.amount_paid'))
                            ->state(fn ($record): string => sprintf('%s %s', $record->currency, number_format((float) $record->amount_paid, 2))),
                        TextEntry::make('document_path')
                            ->label(__('admin.invoices.fields.document_path'))
                            ->default(__('admin.invoices.empty.document_path')),
                        TextEntry::make('status_summary')
                            ->label(__('admin.invoices.fields.status_summary'))
                            ->state(function ($record): string {
                                if ($record->status === InvoiceStatus::PAID) {
                                    return __('admin.invoices.status_summaries.paid');
                                }

                                if ((float) $record->amount_paid > 0) {
                                    return __('admin.invoices.status_summaries.partially_paid');
                                }

                                return __('admin.invoices.status_summaries.outstanding');
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
