<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Actions\Billing\ConfirmInvoicePayment;
use App\Actions\Billing\CreateManualPayment;
use App\Actions\Billing\RejectInvoicePayment;
use App\Actions\Billing\VoidInvoicePayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label(__('admin.payments.columns.tenant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label(__('admin.payments.columns.invoice'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('admin.payments.columns.amount'))
                    ->state(fn (InvoicePayment $record): string => EuMoneyFormatter::format($record->amount, $record->currency))
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label(__('admin.payments.columns.method'))
                    ->state(fn (InvoicePayment $record): string => $record->methodLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.payments.columns.status'))
                    ->state(fn (InvoicePayment $record): string => $record->statusLabel())
                    ->badge()
                    ->color(fn (InvoicePayment $record): string => self::statusColor($record)),
                TextColumn::make('payment_date')
                    ->label(__('admin.payments.columns.payment_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.payments.columns.submitted_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.payments.columns.status'))
                    ->options(PaymentStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forStatusValue($data['value'] ?? null)),
                Filter::make('pending_review')
                    ->label(__('admin.payments.filters.pending_review'))
                    ->query(fn (Builder $query): Builder => $query->where('status', PaymentStatus::PENDING)),
            ])
            ->headerActions([
                self::createManualPaymentAction(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                Action::make('confirmPayment')
                    ->label(__('admin.payments.actions.confirm'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (InvoicePayment $record): bool => $record->canBeConfirmed())
                    ->action(function (InvoicePayment $record, ConfirmInvoicePayment $confirmInvoicePayment): void {
                        $confirmInvoicePayment->handle($record, PaymentResource::currentUser());

                        Notification::make()
                            ->title(__('admin.payments.messages.confirmed'))
                            ->success()
                            ->send();
                    }),
                Action::make('rejectPayment')
                    ->label(__('admin.payments.actions.reject'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->slideOver()
                    ->visible(fn (InvoicePayment $record): bool => $record->canBeRejected())
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label(__('admin.payments.fields.rejection_reason'))
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (InvoicePayment $record, array $data, RejectInvoicePayment $rejectInvoicePayment): void {
                        $rejectInvoicePayment->handle($record, PaymentResource::currentUser(), (string) ($data['rejection_reason'] ?? ''));

                        Notification::make()
                            ->title(__('admin.payments.messages.rejected'))
                            ->success()
                            ->send();
                    }),
                Action::make('voidPayment')
                    ->label(__('admin.payments.actions.void'))
                    ->icon('heroicon-m-no-symbol')
                    ->color('warning')
                    ->slideOver()
                    ->visible(fn (InvoicePayment $record): bool => $record->canBeVoided())
                    ->schema([
                        Textarea::make('void_reason')
                            ->label(__('admin.payments.fields.void_reason'))
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (InvoicePayment $record, array $data, VoidInvoicePayment $voidInvoicePayment): void {
                        $voidInvoicePayment->handle($record, PaymentResource::currentUser(), (string) ($data['void_reason'] ?? ''));

                        Notification::make()
                            ->title(__('admin.payments.messages.voided'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function createManualPaymentAction(): Action
    {
        return Action::make('createManualPayment')
            ->label(__('admin.payments.actions.create_manual'))
            ->icon('heroicon-m-plus-circle')
            ->slideOver()
            ->modalHeading(__('admin.payments.actions.create_manual'))
            ->modalSubmitActionLabel(__('admin.payments.actions.create_manual_submit'))
            ->schema([
                Select::make('invoice_id')
                    ->label(__('admin.payments.fields.invoice'))
                    ->options(fn (): array => self::invoiceOptions())
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->label(__('admin.payments.fields.amount'))
                    ->numeric()
                    ->required(),
                Select::make('payment_method')
                    ->label(__('admin.payments.fields.payment_method'))
                    ->options(PaymentMethod::options())
                    ->default(PaymentMethod::BANK_TRANSFER->value)
                    ->required(),
                DatePicker::make('payment_date')
                    ->label(__('admin.payments.fields.payment_date'))
                    ->default(now()->toDateString())
                    ->required(),
                TextInput::make('reference')
                    ->label(__('admin.payments.fields.reference'))
                    ->maxLength(255),
                TextInput::make('transaction_id')
                    ->label(__('admin.payments.fields.transaction_id'))
                    ->maxLength(255),
                Textarea::make('internal_note')
                    ->label(__('admin.payments.fields.internal_note'))
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('confirm_immediately')
                    ->label(__('admin.payments.fields.confirm_immediately'))
                    ->default(true),
            ])
            ->action(function (array $data, CreateManualPayment $createManualPayment): void {
                $invoice = Invoice::query()
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'tenant_user_id',
                        'invoice_number',
                        'status',
                        'payment_status',
                        'currency',
                        'total_amount',
                        'amount_paid',
                        'paid_amount',
                        'balance_amount',
                        'due_date',
                        'paid_at',
                        'payment_reference',
                        'overdue_at',
                    ])
                    ->findOrFail((int) $data['invoice_id']);

                $createManualPayment->handle($invoice, PaymentResource::currentUser(), $data);

                Notification::make()
                    ->title(__('admin.payments.messages.created'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, string>
     */
    private static function invoiceOptions(): array
    {
        $query = Invoice::query()
            ->select(['id', 'organization_id', 'invoice_number', 'status'])
            ->whereIn('status', [
                InvoiceStatus::FINALIZED,
                InvoiceStatus::PARTIALLY_PAID,
                InvoiceStatus::OVERDUE,
            ])
            ->latestBillingFirst()
            ->limit(100);

        $user = PaymentResource::currentUser();
        $organizationId = app(OrganizationContext::class)->currentOrganizationId() ?? $user?->organization_id;

        if (! ($user?->isSuperadmin() ?? false)) {
            $query->when(
                $organizationId !== null,
                fn (Builder $invoiceQuery): Builder => $invoiceQuery->forOrganization($organizationId),
                fn (Builder $invoiceQuery): Builder => $invoiceQuery->whereKey(-1),
            );
        }

        return $query->pluck('invoice_number', 'id')->all();
    }

    private static function statusColor(InvoicePayment $payment): string
    {
        return match ($payment->status) {
            PaymentStatus::PENDING => 'warning',
            PaymentStatus::CONFIRMED => 'success',
            PaymentStatus::FAILED,
            PaymentStatus::VOIDED => 'danger',
            PaymentStatus::REFUNDED,
            PaymentStatus::PARTIALLY_REFUNDED => 'info',
            default => 'gray',
        };
    }
}
