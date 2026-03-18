<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Filament\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Http\Requests\Admin\Invoices\SendInvoiceEmailRequest;
use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('admin.invoices.columns.invoice_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('admin.invoices.columns.tenant'))
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => ! (auth()->user()?->isTenant() ?? false)),
                TextColumn::make('property.name')
                    ->label(__('admin.invoices.columns.property'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.invoices.columns.status'))
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label(__('admin.invoices.columns.total_amount'))
                    ->formatStateUsing(fn ($state, Invoice $record): string => sprintf('%s %s', $record->currency, number_format((float) $state, 2)))
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('admin.invoices.columns.due_date'))
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Invoice $record): bool => InvoiceResource::canEdit($record)),
                Action::make('finalize')
                    ->label(__('admin.invoices.actions.finalize'))
                    ->icon('heroicon-m-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.invoices.messages.finalized_locked'))
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::DRAFT)
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->action(function (Invoice $record, FinalizeInvoiceAction $finalizeInvoiceAction): void {
                        $finalizeInvoiceAction->handle($record);

                        Notification::make()
                            ->title(__('admin.invoices.messages.finalized'))
                            ->success()
                            ->send();
                    }),
                Action::make('processPayment')
                    ->label(__('admin.invoices.actions.process_payment'))
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => in_array($record->status, [
                        InvoiceStatus::FINALIZED,
                        InvoiceStatus::PARTIALLY_PAID,
                        InvoiceStatus::OVERDUE,
                    ], true))
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->schema([
                        TextInput::make('amount_paid')
                            ->label(__('admin.invoices.fields.amount_paid'))
                            ->numeric()
                            ->required(),
                        DatePicker::make('paid_at')
                            ->label(__('admin.invoices.fields.paid_at'))
                            ->required(),
                        Select::make('method')
                            ->label(__('admin.invoices.fields.method'))
                            ->options(PaymentMethod::options())
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label(__('admin.invoices.fields.payment_reference'))
                            ->maxLength(255),
                    ])
                    ->action(function (Invoice $record, array $data, RecordInvoicePaymentAction $recordInvoicePaymentAction): void {
                        $recordInvoicePaymentAction->handle($record, $data);

                        Notification::make()
                            ->title(__('admin.invoices.messages.payment_recorded'))
                            ->success()
                            ->send();
                    }),
                Action::make('sendEmail')
                    ->label(__('admin.invoices.actions.send_email'))
                    ->icon('heroicon-m-envelope')
                    ->visible(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->schema([
                        TextInput::make('recipient_email')
                            ->label(__('admin.invoices.fields.recipient_email'))
                            ->email()
                            ->required()
                            ->default(fn (Invoice $record): string => (string) ($record->tenant?->email ?? '')),
                    ])
                    ->action(function (Invoice $record, array $data, SendInvoiceEmailAction $sendInvoiceEmailAction): void {
                        $validated = (new SendInvoiceEmailRequest)
                            ->validatePayload($data, auth()->user());

                        $sendInvoiceEmailAction->handle($record, auth()->user(), $validated['recipient_email']);

                        Notification::make()
                            ->title(__('admin.invoices.messages.email_sent'))
                            ->success()
                            ->send();
                    }),
                Action::make('downloadPdf')
                    ->label(__('admin.invoices.actions.download_pdf'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->authorize(fn (Invoice $record): bool => auth()->user()?->can('download', $record) ?? false)
                    ->action(fn (Invoice $record, InvoicePdfService $invoicePdfService) => $invoicePdfService->streamDownload($record)),
            ])
            ->defaultSort('billing_period_start', 'desc');
    }
}
