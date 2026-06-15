<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPeriods\Tables;

use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use App\Models\BillingPeriod;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BillingPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.billing_periods.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label(__('admin.billing_periods.fields.starts_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('admin.billing_periods.fields.ends_at'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reading_submission_deadline')
                    ->label(__('admin.billing_periods.fields.reading_submission_deadline'))
                    ->date()
                    ->sortable(),
                TextColumn::make('invoice_generation_date')
                    ->label(__('admin.billing_periods.fields.invoice_generation_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('payment_due_date')
                    ->label(__('admin.billing_periods.fields.payment_due_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reading_request_invoices_count')
                    ->label(__('admin.billing_periods.fields.reading_request_invoices_count'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('invoices_count')
                    ->label(__('admin.billing_periods.fields.invoices_count'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->forOrganization((int) $data['value'])
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->authorize(fn (BillingPeriod $record): bool => BillingPeriodResource::canEdit($record)),
                Action::make('openReadingCycle')
                    ->label(__('admin.billing_periods.actions.open_reading_cycle'))
                    ->icon('heroicon-m-clipboard-document-list')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.billing_periods.actions.open_reading_cycle'))
                    ->modalDescription(__('admin.billing_periods.actions.open_reading_cycle_description'))
                    ->authorize(fn (BillingPeriod $record): bool => BillingPeriodResource::canEdit($record))
                    ->visible(fn (BillingPeriod $record): bool => $record->starts_at !== null
                        && $record->ends_at !== null
                        && $record->reading_submission_deadline !== null)
                    ->action(function (BillingPeriod $record, OpenReadingInvoiceCycleAction $openReadingInvoiceCycle): void {
                        $actor = self::currentUser();

                        if (! $actor instanceof User) {
                            abort(403);
                        }

                        $record->loadMissing('organization');

                        if (! $record->organization instanceof Organization) {
                            abort(403);
                        }

                        $result = $openReadingInvoiceCycle->handle($record->organization, [
                            'billing_period_start' => $record->starts_at?->toDateString(),
                            'billing_period_end' => $record->ends_at?->toDateString(),
                            'due_date' => $record->reading_submission_deadline?->toDateString(),
                            'invoice_generation_date' => $record->invoice_generation_date?->toDateString(),
                            'payment_due_date' => $record->payment_due_date?->toDateString(),
                        ], $actor);

                        Notification::make()
                            ->success()
                            ->title(__('admin.billing_periods.messages.reading_cycle_opened', [
                                'created' => $result['created']->count(),
                                'notified' => $result['notified'],
                                'skipped' => count($result['skipped']),
                            ]))
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->authorize(fn (BillingPeriod $record): bool => BillingPeriodResource::canDelete($record)),
            ])
            ->emptyStateHeading(__('admin.billing_periods.empty_state.heading'))
            ->emptyStateDescription(__('admin.billing_periods.empty_state.description'))
            ->emptyStateActions([
                Action::make('createBillingPeriod')
                    ->label(__('admin.billing_periods.empty_state.action'))
                    ->icon('heroicon-m-plus')
                    ->button()
                    ->url(fn (): string => BillingPeriodResource::getUrl('create')),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
