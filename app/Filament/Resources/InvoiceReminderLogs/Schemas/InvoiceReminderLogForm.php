<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceReminderLogForm
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
                TextInput::make('channel')
                    ->required()
                    ->default('email'),
                DateTimePicker::make('sent_at')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
