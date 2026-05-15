<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\RecordInvoicePaymentAction;
use App\Filament\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\Admin\Invoices\InvoiceTablePresenter;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        self::overrideFilterResetLabel();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->forIdValues(request()->query('created_invoice_ids')))
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('invoice_number')
                    ->label(__('admin.invoices.columns.invoice_number'))
                    ->fontFamily('mono')
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('admin.invoices.columns.tenant'))
                    ->description(fn (Invoice $record): string => InvoiceTablePresenter::tenantDescription($record))
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->searchTenantName($search),
                    )
                    ->sortable(),
                TextColumn::make('billing_period_start')
                    ->label(__('admin.invoices.columns.billing_period'))
                    ->state(fn (Invoice $record): string => InvoiceTablePresenter::billingPeriod($record))
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('admin.invoices.columns.amount'))
                    ->state(fn (Invoice $record): string => InvoiceTablePresenter::amount($record))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.invoices.columns.status'))
                    ->state(fn (Invoice $record): InvoiceStatus => InvoiceTablePresenter::status($record))
                    ->badge()
                    ->color(fn (Invoice $record): string => InvoiceTablePresenter::statusColor($record))
                    ->sortable(),
                TextColumn::make('finalized_at')
                    ->label(__('admin.invoices.columns.issued_date'))
                    ->state(fn (Invoice $record): string => InvoiceTablePresenter::issuedDate($record))
                    ->color(fn (Invoice $record): ?string => $record->finalized_at === null ? 'gray' : null)
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label(__('admin.invoices.columns.paid_date'))
                    ->state(fn (Invoice $record): string => InvoiceTablePresenter::paidDate($record))
                    ->color(fn (Invoice $record): ?string => $record->paid_at === null ? 'gray' : null)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                SelectFilter::make('status')
                    ->label(__('admin.invoices.columns.status'))
                    ->placeholder(__('admin.invoices.filters.all_statuses'))
                    ->multiple()
                    ->options(InvoiceStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forEffectiveStatusValues($data['values'] ?? [])),
                Filter::make('billing_period')
                    ->label(__('admin.invoices.columns.billing_period'))
                    ->schema([
                        DatePicker::make('billing_period_from')
                            ->label(__('admin.filters.from')),
                        DatePicker::make('billing_period_to')
                            ->label(__('admin.filters.to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->forBillingPeriodRange(
                        $data['billing_period_from'] ?? null,
                        $data['billing_period_to'] ?? null,
                    )),
                SelectFilter::make('property_id')
                    ->label(__('admin.invoices.columns.property'))
                    ->placeholder(__('admin.invoices.filters.all_properties'))
                    ->options(fn (): array => self::propertyFilterOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->forPropertyValue($data['value'] ?? null)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->visible(fn (Invoice $record): bool => $record->canViewFromAdminWorkspace()),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->visible(fn (Invoice $record): bool => $record->canEditFromAdminWorkspace() && InvoiceResource::canEdit($record)),
                Action::make('finalize')
                    ->label(__('admin.invoices.actions.finalize'))
                    ->icon('heroicon-m-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Invoice $record): string => __('admin.invoices.actions.finalize_heading', [
                        'number' => $record->invoice_number,
                    ]))
                    ->modalDescription(__('admin.invoices.messages.finalize_confirmation'))
                    ->modalSubmitActionLabel(__('admin.invoices.actions.finalize_invoice'))
                    ->visible(fn (Invoice $record): bool => $record->canFinalizeFromAdminWorkspace())
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->action(function (Invoice $record, FinalizeInvoiceAction $finalizeInvoiceAction): void {
                        $finalizeInvoiceAction->handle($record);

                        Notification::make()
                            ->title(__('admin.invoices.messages.finalized_named', [
                                'number' => $record->invoice_number,
                            ]))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->visible(fn (Invoice $record): bool => $record->canBeDeletedFromAdminWorkspace())
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canDelete($record)),
                Action::make('processPayment')
                    ->label(__('admin.invoices.actions.process_payment'))
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->slideOver()
                    ->modalHeading(fn (Invoice $record): string => __('admin.invoices.actions.record_payment_heading', [
                        'number' => $record->invoice_number,
                    ]))
                    ->modalSubmitActionLabel(__('admin.invoices.actions.record_payment'))
                    ->visible(fn (Invoice $record): bool => $record->canProcessPaymentFromAdminWorkspace())
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->schema([
                        TextInput::make('amount_paid')
                            ->label(__('admin.invoices.fields.payment_amount'))
                            ->numeric()
                            ->required()
                            ->default(fn (Invoice $record): float => (float) $record->total_amount),
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
                    ->slideOver()
                    ->visible(fn (Invoice $record): bool => $record->canSendEmailFromAdminWorkspace())
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->modalHeading(__('admin.invoices.actions.send_invoice_heading'))
                    ->modalSubmitActionLabel(__('admin.invoices.actions.send_invoice'))
                    ->schema([
                        TextInput::make('recipient_email')
                            ->label(__('admin.invoices.fields.recipient_email'))
                            ->email()
                            ->required()
                            ->default(fn (Invoice $record): string => (string) ($record->tenant?->email ?? '')),
                        Textarea::make('personal_message')
                            ->label(__('admin.invoices.fields.personal_message'))
                            ->rows(4),
                    ])
                    ->action(function (Invoice $record, array $data, SendInvoiceEmailAction $sendInvoiceEmailAction): void {
                        $sendInvoiceEmailAction->handle(
                            $record,
                            static::currentUser(),
                            $data['recipient_email'] ?? null,
                            $data['personal_message'] ?? null,
                        );

                        Notification::make()
                            ->title(__('admin.invoices.messages.email_queued'))
                            ->success()
                            ->send();
                    }),
                Action::make('sendReminder')
                    ->label(__('admin.invoices.actions.send_reminder'))
                    ->icon('heroicon-m-bell-alert')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Invoice $record): string => __('admin.invoices.messages.send_reminder_confirmation', [
                        'number' => $record->invoice_number,
                    ]))
                    ->visible(fn (Invoice $record): bool => $record->canSendReminderFromAdminWorkspace())
                    ->authorize(fn (Invoice $record): bool => InvoiceResource::canEdit($record))
                    ->action(function (Invoice $record, SendInvoiceReminderAction $sendInvoiceReminderAction): void {
                        $queued = $sendInvoiceReminderAction->handle($record, static::currentUser());
                        $notification = Notification::make()
                            ->title($queued
                                ? __('admin.invoices.messages.reminder_sent')
                                : __('admin.invoices.messages.reminder_not_sent'));

                        if ($queued) {
                            $notification->success();
                        } else {
                            $notification->warning();
                        }

                        $notification->send();
                    }),
                Action::make('downloadPdf')
                    ->label(__('admin.invoices.actions.download_pdf'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->visible(fn (Invoice $record): bool => $record->canViewFromAdminWorkspace())
                    ->authorize(fn (Invoice $record): bool => static::currentUser()?->can('download', $record) ?? false)
                    ->action(fn (Invoice $record, InvoicePdfService $invoicePdfService) => $invoicePdfService->streamDownload($record)),
            ])
            ->searchPlaceholder(__('admin.invoices.search_placeholder'))
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('billing_period_start', 'desc');
    }

    /**
     * @return array<int, string>
     */
    private static function propertyFilterOptions(): array
    {
        $query = Property::query()
            ->select(['id', 'organization_id', 'name'])
            ->orderBy('name')
            ->orderBy('id');

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
        $user = static::currentUser();

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        } elseif (! ($user instanceof User && $user->isSuperadmin())) {
            $query->whereKey(-1);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Property $property): array => [$property->id => $property->displayName()])
            ->all();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => __('admin.actions.clear_all_filters'),
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => __('admin.actions.clear_all_filters', locale: 'lt'),
        ], 'lt', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => __('admin.actions.clear_all_filters', locale: 'ru'),
        ], 'ru', 'filament-tables');
    }
}
