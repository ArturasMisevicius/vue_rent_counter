<?php

namespace App\Filament\Resources\InvoicePayments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoicePaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')
                    ->label('Invoice'),
                TextEntry::make('organization.name')
                    ->label('Organization'),
                TextEntry::make('recorded_by_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('method')
                    ->badge(),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('paid_at')
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
