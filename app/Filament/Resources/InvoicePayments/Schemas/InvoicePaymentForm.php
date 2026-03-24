<?php

namespace App\Filament\Resources\InvoicePayments\Schemas;

use App\Enums\PaymentMethod;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoicePaymentForm
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
                Select::make('recorded_by_user_id')
                    ->relationship('recordedBy', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Select::make('method')
                    ->options(PaymentMethod::class)
                    ->required(),
                TextInput::make('reference'),
                DateTimePicker::make('paid_at')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
