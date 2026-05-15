<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.invoices');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('invoices_count');

        return (string) ($count ?? $ownerRecord->invoices()->count());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAdminWorkspaceRelations()->latestBillingFirst())
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('admin.tenants.invoices.columns.invoice_number'))
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->label(__('admin.tenants.invoices.columns.billing_period'))
                    ->state(fn (Invoice $record): string => collect([
                        $record->billing_period_start?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                        $record->billing_period_end?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                    ])->filter()->implode(' - ')),
                TextColumn::make('total_amount')
                    ->label(__('admin.tenants.invoices.columns.total_amount'))
                    ->state(fn (Invoice $record): string => EuMoneyFormatter::format($record->total_amount, $record->currency)),
                TextColumn::make('status')
                    ->label(__('admin.tenants.invoices.columns.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.tenants.invoices.columns.issued_date'))
                    ->state(fn (Invoice $record): string => $record->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—'),
                TextColumn::make('paid_at')
                    ->label(__('admin.tenants.invoices.columns.paid_date'))
                    ->state(fn (Invoice $record): string => $record->paid_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record])),
                Action::make('downloadPdf')
                    ->label(__('admin.invoices.actions.download_pdf'))
                    ->action(fn (Invoice $record, InvoicePdfService $invoicePdfService) => $invoicePdfService->streamDownload($record)),
                Action::make('sendEmail')
                    ->label(__('admin.tenants.invoices.actions.send_invoice_email'))
                    ->schema([
                        TextInput::make('recipient_email')
                            ->label(__('admin.invoices.fields.recipient_email'))
                            ->email()
                            ->required()
                            ->default(fn (Invoice $record): string => (string) ($record->tenant?->email ?? '')),
                        Textarea::make('personal_message')
                            ->label(__('admin.tenants.invoices.fields.personal_message'))
                            ->rows(4),
                    ])
                    ->action(function (Invoice $record, array $data, SendInvoiceEmailAction $sendInvoiceEmailAction): void {
                        $sendInvoiceEmailAction->handle(
                            $record,
                            self::currentUser(),
                            $data['recipient_email'] ?? null,
                            $data['personal_message'] ?? null,
                        );

                        Notification::make()
                            ->success()
                            ->title(__('admin.invoices.messages.email_queued'))
                            ->send();
                    }),
            ])
            ->defaultSort('billing_period_start', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
