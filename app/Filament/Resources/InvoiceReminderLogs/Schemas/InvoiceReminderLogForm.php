<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Schemas;

use App\Filament\Support\Localization\LocalizedCodeLabel;
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
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.invoice'))
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('organization_id')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('sent_by_user_id')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.sent_by'))
                    ->relationship('sentBy', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('recipient_email')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.recipient_email'))
                    ->email()
                    ->required(),
                Select::make('channel')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.channel'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.invoice_reminder_logs.channels', [
                        'email',
                        'sms',
                        'postal',
                    ]))
                    ->required()
                    ->default('email'),
                DateTimePicker::make('sent_at')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.sent_at'))
                    ->required(),
                Textarea::make('notes')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
