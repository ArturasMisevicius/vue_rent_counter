<?php

namespace App\Filament\Resources\InvoicePayments\Schemas;

use App\Models\InvoicePayment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoicePaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')->label(__('superadmin.relation_resources.invoice_payments.fields.invoice')),
                TextEntry::make('organization.name')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.organization')),
                TextEntry::make('recorded_by_user_id')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.recorded_by'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('amount')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.amount'))
                    ->numeric(),
                TextEntry::make('method')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.method'))
                    ->state(fn (InvoicePayment $record): string => $record->methodLabel())
                    ->badge(),
                TextEntry::make('reference')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.reference'))
                    ->placeholder('-'),
                TextEntry::make('paid_at')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.paid_at'))
                    ->dateTime(),
                TextEntry::make('notes')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.notes'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
