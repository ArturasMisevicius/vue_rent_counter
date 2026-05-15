<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Schemas;

use App\Models\InvoiceReminderLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceReminderLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.invoice')),
                TextEntry::make('organization.name')->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.organization')),
                TextEntry::make('sent_by_user_id')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.sent_by'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('recipient_email')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.recipient_email')),
                TextEntry::make('channel')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.channel'))
                    ->state(fn (InvoiceReminderLog $record): string => $record->channelLabel()),
                TextEntry::make('sent_at')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.sent_at'))
                    ->dateTime(),
                TextEntry::make('notes')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.notes'))
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
