<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceEmailLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('sent_by_user_id')
                    ->relationship('sentBy', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('recipient_email')
                    ->email()
                    ->required(),
                TextInput::make('subject'),
                TextInput::make('status')
                    ->required()
                    ->default('sent'),
                DateTimePicker::make('sent_at')
                    ->required(),
            ]);
    }
}
