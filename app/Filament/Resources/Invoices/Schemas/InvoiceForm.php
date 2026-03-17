<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;
use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.invoices.sections.details'))
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label(__('admin.invoices.fields.invoice_number'))
                            ->maxLength(255)
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'invoice_number')),
                        Select::make('status')
                            ->label(__('admin.invoices.fields.status'))
                            ->options(InvoiceStatus::options())
                            ->required(),
                        TextInput::make('total_amount')
                            ->label(__('admin.invoices.fields.total_amount'))
                            ->numeric()
                            ->required()
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'total_amount')),
                        DatePicker::make('billing_period_start')
                            ->label(__('admin.invoices.fields.billing_period_start'))
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'billing_period_start')),
                        DatePicker::make('billing_period_end')
                            ->label(__('admin.invoices.fields.billing_period_end'))
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'billing_period_end')),
                        DatePicker::make('due_date')
                            ->label(__('admin.invoices.fields.due_date'))
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'due_date')),
                        TextInput::make('amount_paid')
                            ->label(__('admin.invoices.fields.amount_paid'))
                            ->numeric(),
                        TextInput::make('payment_reference')
                            ->label(__('admin.invoices.fields.payment_reference'))
                            ->maxLength(255),
                        DateTimePicker::make('paid_at')
                            ->label(__('admin.invoices.fields.paid_at')),
                    ])
                    ->columns(2),
                Section::make(__('admin.invoices.sections.amounts'))
                    ->schema([
                        Repeater::make('items')
                            ->label(__('admin.invoices.fields.items'))
                            ->schema([
                                TextInput::make('description')
                                    ->required(),
                                TextInput::make('amount')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'items')),
                        Textarea::make('notes')
                            ->label(__('admin.invoices.fields.notes'))
                            ->rows(4)
                            ->hidden(fn (?Invoice $record): bool => self::shouldHideField($record, 'notes')),
                    ]),
            ]);
    }

    private static function shouldHideField(?Invoice $record, string $field): bool
    {
        if (! $record instanceof Invoice) {
            return false;
        }

        $guard = app(FinalizedInvoiceGuard::class);

        if (! $guard->isImmutable($record)) {
            return false;
        }

        return ! $guard->canMutateField($field);
    }
}
