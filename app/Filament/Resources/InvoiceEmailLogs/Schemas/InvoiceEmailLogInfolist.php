<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Schemas;

use App\Models\InvoiceEmailLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceEmailLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')->label(__('superadmin.relation_resources.invoice_email_logs.fields.invoice')),
                TextEntry::make('organization.name')->label(__('superadmin.relation_resources.invoice_email_logs.fields.organization')),
                TextEntry::make('sent_by_user_id')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.sent_by'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('recipient_email')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.recipient_email')),
                TextEntry::make('subject')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.subject'))
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.status'))
                    ->state(fn (InvoiceEmailLog $record): string => $record->statusLabel()),
                TextEntry::make('sent_at')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.sent_at'))
                    ->dateTime(),
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
