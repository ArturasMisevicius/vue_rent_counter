<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.invoices.view_title'))
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label(__('admin.invoices.fields.invoice_number')),
                        TextEntry::make('tenant.name')
                            ->label(__('admin.invoices.fields.tenant')),
                        TextEntry::make('property.name')
                            ->label(__('admin.invoices.fields.property')),
                        TextEntry::make('status')
                            ->label(__('admin.invoices.fields.status'))
                            ->formatStateUsing(fn ($state): string => __('admin.invoices.statuses.'.($state->value ?? $state))),
                        TextEntry::make('total_amount')
                            ->label(__('admin.invoices.fields.total_amount')),
                        TextEntry::make('amount_paid')
                            ->label(__('admin.invoices.fields.amount_paid')),
                    ])
                    ->columns(2),
                Section::make(__('admin.invoices.sections.line_items'))
                    ->schema([
                        TextEntry::make('line_items')
                            ->label(__('admin.invoices.fields.line_items'))
                            ->state(function (Invoice $record): string {
                                return $record->invoiceItems
                                    ->map(fn (InvoiceItem $item): string => "{$item->description} · {$item->total}")
                                    ->implode("\n");
                            }),
                    ]),
                Section::make(__('admin.invoices.sections.history'))
                    ->schema([
                        TextEntry::make('payments_summary')
                            ->label(__('admin.invoices.fields.payments'))
                            ->state(fn (Invoice $record): string => $record->payments
                                ->map(fn (InvoicePayment $payment): string => "{$payment->amount} · ".($payment->paid_at?->format('Y-m-d') ?? ''))
                                ->implode("\n")),
                        TextEntry::make('emails_summary')
                            ->label(__('admin.invoices.fields.email_history'))
                            ->state(fn (Invoice $record): string => $record->emailLogs
                                ->map(fn (InvoiceEmailLog $log): string => "{$log->recipient_email} · ".($log->sent_at?->format('Y-m-d H:i') ?? ''))
                                ->implode("\n")),
                        TextEntry::make('reminders_summary')
                            ->label(__('admin.invoices.fields.reminder_history'))
                            ->state(fn (Invoice $record): string => $record->reminderLogs
                                ->map(fn (InvoiceReminderLog $log): string => "{$log->recipient_email} · ".($log->sent_at?->format('Y-m-d H:i') ?? ''))
                                ->implode("\n")),
                    ]),
            ]);
    }
}
