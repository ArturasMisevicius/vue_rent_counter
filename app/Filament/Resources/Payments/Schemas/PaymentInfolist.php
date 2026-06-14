<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\InvoicePayment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.invoice_number')
                    ->label(__('admin.payments.fields.invoice')),
                TextEntry::make('tenant.name')
                    ->label(__('admin.payments.fields.tenant'))
                    ->placeholder('-'),
                TextEntry::make('property.name')
                    ->label(__('admin.payments.fields.property'))
                    ->placeholder('-'),
                TextEntry::make('amount')
                    ->label(__('admin.payments.fields.amount'))
                    ->numeric(),
                TextEntry::make('currency')
                    ->label(__('admin.payments.fields.currency')),
                TextEntry::make('payment_method')
                    ->label(__('admin.payments.fields.payment_method'))
                    ->state(fn (InvoicePayment $record): string => $record->methodLabel())
                    ->badge(),
                TextEntry::make('status')
                    ->label(__('admin.payments.fields.status'))
                    ->state(fn (InvoicePayment $record): string => $record->statusLabel())
                    ->badge(),
                TextEntry::make('payment_date')
                    ->label(__('admin.payments.fields.payment_date'))
                    ->date(),
                TextEntry::make('reference')
                    ->label(__('admin.payments.fields.reference'))
                    ->placeholder('-'),
                TextEntry::make('transaction_id')
                    ->label(__('admin.payments.fields.transaction_id'))
                    ->placeholder('-'),
                TextEntry::make('tenant_comment')
                    ->label(__('admin.payments.fields.tenant_comment'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('internal_note')
                    ->label(__('admin.payments.fields.internal_note'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('rejection_reason')
                    ->label(__('admin.payments.fields.rejection_reason'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('void_reason')
                    ->label(__('admin.payments.fields.void_reason'))
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
