<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Tables;

use App\Enums\ExtraChargeStatus;
use App\Enums\ExtraChargeTypeCode;
use App\Enums\InvoiceItemSourceType;
use App\Filament\Actions\Admin\ExtraCharges\ApproveExtraChargeAction;
use App\Filament\Actions\Admin\ExtraCharges\RejectExtraChargeAction;
use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\ExtraCharge;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExtraChargesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyAttentionQuery($query))
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('admin.extra_charges.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->label(__('admin.extra_charges.fields.extra_charge_type'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('admin.extra_charges.fields.tenant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label(__('admin.extra_charges.fields.property'))
                    ->state(fn (ExtraCharge $record): string => $record->property?->displayName() ?? '—')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('admin.extra_charges.fields.total_amount'))
                    ->state(fn (ExtraCharge $record): string => EuMoneyFormatter::format($record->total_amount, $record->currency))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.extra_charges.fields.status'))
                    ->state(fn (ExtraCharge $record): string => $record->statusLabel())
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_recurring')
                    ->label(__('admin.extra_charges.fields.is_recurring'))
                    ->boolean(),
                TextColumn::make('invoice.invoice_number')
                    ->label(__('admin.extra_charges.fields.invoice'))
                    ->url(fn (ExtraCharge $record): ?string => $record->invoice_id !== null
                        ? InvoiceResource::getUrl('view', ['record' => $record->invoice_id])
                        : null)
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->label(__('admin.extra_charges.fields.starts_at'))
                    ->date()
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
                SelectFilter::make('status')
                    ->label(__('admin.extra_charges.fields.status'))
                    ->options(ExtraChargeStatus::options()),
                SelectFilter::make('type_code')
                    ->label(__('admin.extra_charges.fields.type'))
                    ->options(ExtraChargeTypeCode::options())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('type', fn (Builder $typeQuery): Builder => $typeQuery->where('type', $data['value']))
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->authorize(fn (ExtraCharge $record): bool => ExtraChargeResource::canEdit($record)),
                Action::make('approve')
                    ->label(__('admin.extra_charges.actions.approve'))
                    ->requiresConfirmation()
                    ->authorize(fn (ExtraCharge $record): bool => self::currentUser()?->can('approve', $record) ?? false)
                    ->visible(fn (ExtraCharge $record): bool => in_array($record->status, [ExtraChargeStatus::DRAFT, ExtraChargeStatus::PENDING_REVIEW], true))
                    ->action(function (ExtraCharge $record, ApproveExtraChargeAction $action): void {
                        $actor = self::currentUser();

                        if (! $actor instanceof User) {
                            abort(403);
                        }

                        $action->handle($actor, $record);

                        Notification::make()
                            ->success()
                            ->title(__('admin.extra_charges.messages.approved'))
                            ->send();
                    }),
                Action::make('reject')
                    ->label(__('admin.extra_charges.actions.reject'))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('internal_note')
                            ->label(__('admin.extra_charges.fields.internal_note'))
                            ->rows(3),
                    ])
                    ->authorize(fn (ExtraCharge $record): bool => self::currentUser()?->can('reject', $record) ?? false)
                    ->visible(fn (ExtraCharge $record): bool => $record->status !== ExtraChargeStatus::INCLUDED_IN_INVOICE)
                    ->action(function (ExtraCharge $record, array $data, RejectExtraChargeAction $action): void {
                        $actor = self::currentUser();

                        if (! $actor instanceof User) {
                            abort(403);
                        }

                        $action->handle($actor, $record, $data['internal_note'] ?? null);

                        Notification::make()
                            ->success()
                            ->title(__('admin.extra_charges.messages.rejected'))
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->authorize(fn (ExtraCharge $record): bool => ExtraChargeResource::canDelete($record)),
            ])
            ->emptyStateHeading(__('admin.extra_charges.empty_state.heading'))
            ->emptyStateDescription(__('admin.extra_charges.empty_state.description'))
            ->emptyStateActions([
                Action::make('createExtraCharge')
                    ->label(__('admin.extra_charges.empty_state.action'))
                    ->icon('heroicon-m-plus')
                    ->button()
                    ->url(fn (): string => ExtraChargeResource::getUrl('create')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function applyAttentionQuery(Builder $query): Builder
    {
        $attention = request()->query('attention');

        if (! is_string($attention) || $attention === '') {
            return $query;
        }

        return match ($attention) {
            'included_twice' => $query->whereIn('id', self::includedTwiceChargeIdsForCurrentWorkspace()),
            default => $query,
        };
    }

    /**
     * @return list<int>
     */
    private static function includedTwiceChargeIdsForCurrentWorkspace(): array
    {
        $user = self::currentUser();

        return InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id'])
            ->where('source_type', InvoiceItemSourceType::EXTRA_CHARGE)
            ->whereNotNull('source_id')
            ->when(
                ! ($user?->isSuperadmin() ?? false),
                fn (Builder $query): Builder => $user?->organization_id === null
                    ? $query->whereKey(-1)
                    : $query->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery->forOrganization((int) $user->organization_id)),
            )
            ->get()
            ->groupBy('source_id')
            ->filter(fn ($group): bool => $group->count() > 1)
            ->keys()
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
