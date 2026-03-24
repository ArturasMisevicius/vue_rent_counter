<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Filament\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\Admin\Invoices\InvoiceViewPresenter;
use App\Services\Billing\InvoicePdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoices.pages.view-invoice';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $pageDataCache = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->refreshRecord();
    }

    public function getBreadcrumbs(): array
    {
        return [
            InvoiceResource::getUrl('index') => InvoiceResource::getPluralModelLabel(),
            $this->record->invoice_number,
        ];
    }

    public function getTitle(): string
    {
        return (string) $this->record->invoice_number;
    }

    public function getSubheading(): ?string
    {
        return (string) ($this->pageData()['subtitle'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function pageData(): array
    {
        return $this->pageDataCache ??= app(InvoiceViewPresenter::class)->present($this->record);
    }

    protected function getHeaderActions(): array
    {
        if ($this->record->canEditFromAdminWorkspace()) {
            return [
                EditAction::make()
                    ->label(__('admin.actions.edit')),
                Action::make('finalize')
                    ->label(__('admin.invoices.actions.finalize'))
                    ->icon('heroicon-m-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.invoices.actions.finalize_heading', [
                        'number' => $this->record->invoice_number,
                    ]))
                    ->modalDescription(__('admin.invoices.messages.finalize_confirmation'))
                    ->modalSubmitActionLabel(__('admin.invoices.actions.finalize_invoice'))
                    ->action(function (FinalizeInvoiceAction $finalizeInvoiceAction): void {
                        $finalizeInvoiceAction->handle($this->record);
                        $this->refreshRecord();

                        Notification::make()
                            ->title(__('admin.invoices.messages.finalized_named', [
                                'number' => $this->record->invoice_number,
                            ]))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->authorize(fn (): bool => InvoiceResource::canDelete($this->record)),
            ];
        }

        $actions = [];

        if ($this->record->canProcessPaymentFromAdminWorkspace()) {
            $actions[] = Action::make('processPayment')
                ->label(__('admin.invoices.actions.process_payment'))
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->slideOver()
                ->modalHeading(__('admin.invoices.actions.record_payment_heading', [
                    'number' => $this->record->invoice_number,
                ]))
                ->modalSubmitActionLabel(__('admin.invoices.actions.record_payment'))
                ->schema([
                    TextInput::make('amount_paid')
                        ->label(__('admin.invoices.fields.payment_amount'))
                        ->numeric()
                        ->required()
                        ->default((float) $this->record->total_amount),
                    DatePicker::make('paid_at')
                        ->label(__('admin.invoices.fields.payment_date'))
                        ->required()
                        ->default(now()->toDateString()),
                    Select::make('method')
                        ->label(__('admin.invoices.fields.payment_method'))
                        ->options(PaymentMethod::options())
                        ->required(),
                    TextInput::make('payment_reference')
                        ->label(__('admin.invoices.fields.payment_reference'))
                        ->maxLength(255),
                ])
                ->action(function (array $data, RecordInvoicePaymentAction $recordInvoicePaymentAction): void {
                    $recordInvoicePaymentAction->handle($this->record, $data);
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('admin.invoices.messages.payment_recorded'))
                        ->success()
                        ->send();
                });
        }

        if ($this->record->canSendEmailFromAdminWorkspace()) {
            $actions[] = Action::make('sendEmail')
                ->label(__('admin.invoices.actions.send_email'))
                ->icon('heroicon-m-envelope')
                ->slideOver()
                ->modalHeading(__('admin.invoices.actions.send_invoice_heading'))
                ->modalSubmitActionLabel(__('admin.invoices.actions.send_invoice'))
                ->schema([
                    TextInput::make('recipient_email')
                        ->label(__('admin.invoices.fields.recipient_email'))
                        ->email()
                        ->required()
                        ->default((string) ($this->record->tenant?->email ?? '')),
                    Textarea::make('personal_message')
                        ->label(__('admin.invoices.fields.personal_message'))
                        ->rows(4),
                ])
                ->action(function (array $data, SendInvoiceEmailAction $sendInvoiceEmailAction): void {
                    $sendInvoiceEmailAction->handle(
                        $this->record,
                        auth()->user(),
                        $data['recipient_email'] ?? null,
                        $data['personal_message'] ?? null,
                    );
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('admin.invoices.messages.email_queued'))
                        ->success()
                        ->send();
                });
        }

        if ($this->record->canSendReminderFromAdminWorkspace()) {
            $actions[] = Action::make('sendReminder')
                ->label(__('admin.invoices.actions.send_reminder'))
                ->icon('heroicon-m-bell-alert')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(__('admin.invoices.messages.send_reminder_confirmation', [
                    'number' => $this->record->invoice_number,
                ]))
                ->action(function (SendInvoiceReminderAction $sendInvoiceReminderAction): void {
                    $queued = $sendInvoiceReminderAction->handle($this->record, auth()->user());
                    $this->refreshRecord();

                    $notification = Notification::make()->title($queued
                        ? __('admin.invoices.messages.reminder_sent')
                        : __('admin.invoices.messages.reminder_not_sent'));

                    if ($queued) {
                        $notification->success();
                    } else {
                        $notification->warning();
                    }

                    $notification->send();
                });
        }

        $actions[] = Action::make('downloadPdf')
            ->label(__('admin.invoices.actions.download_pdf'))
            ->icon('heroicon-m-arrow-down-tray')
            ->action(fn (InvoicePdfService $invoicePdfService) => $invoicePdfService->streamDownload($this->record));

        return $actions;
    }

    private function refreshRecord(): void
    {
        $this->record = InvoiceResource::getEloquentQuery()
            ->with([
                'payments:id,invoice_id,organization_id,recorded_by_user_id,amount,method,reference,paid_at,notes',
                'emailLogs:id,invoice_id,organization_id,sent_by_user_id,recipient_email,subject,status,sent_at,personal_message',
                'reminderLogs:id,invoice_id,organization_id,sent_by_user_id,recipient_email,channel,sent_at,notes',
            ])
            ->findOrFail($this->record->getKey());

        $this->pageDataCache = null;
    }
}
