<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceReminderLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')->label(__('admin.invoices.singular')),
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('sent_by_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('recipient_email'),
                TextEntry::make('channel'),
                TextEntry::make('sent_at')
                    ->dateTime(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
