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
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.invoice'))
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('organization_id')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('recorded_by_user_id')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.recorded_by'))
                    ->relationship('recordedBy', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('amount')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.amount'))
                    ->required()
                    ->numeric(),
                Select::make('method')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.method'))
                    ->options(PaymentMethod::options())
                    ->required(),
                TextInput::make('reference')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.reference')),
                DateTimePicker::make('paid_at')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.paid_at'))
                    ->required(),
                Textarea::make('notes')
                    ->label(__('superadmin.relation_resources.invoice_payments.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
