<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Schemas;

use App\Filament\Support\Localization\LocalizedCodeLabel;
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
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.invoice'))
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('organization_id')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('sent_by_user_id')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.sent_by'))
                    ->relationship('sentBy', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('recipient_email')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.recipient_email'))
                    ->email()
                    ->required(),
                TextInput::make('subject')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.subject')),
                Select::make('status')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.status'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.invoice_email_logs.statuses', [
                        'sent',
                        'failed',
                        'delivered',
                    ]))
                    ->required()
                    ->default('sent'),
                DateTimePicker::make('sent_at')
                    ->label(__('superadmin.relation_resources.invoice_email_logs.fields.sent_at'))
                    ->required(),
            ]);
    }
}
