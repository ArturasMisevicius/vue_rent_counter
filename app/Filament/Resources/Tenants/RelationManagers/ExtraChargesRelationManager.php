<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Actions\Admin\ExtraCharges\CreateExtraChargeAction;
use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use App\Filament\Resources\ExtraCharges\Schemas\ExtraChargeForm;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\ExtraCharge;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ExtraChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'extraCharges';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.extra_charges.panels.charges_services');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('extra_charges_count');

        return (string) ($count ?? $ownerRecord->extraCharges()->count());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withIndexRelations())
            ->columns(self::columns())
            ->headerActions([
                $this->createChargeAction('addOneTimeCharge', false),
                $this->createChargeAction('addRecurringService', true),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (ExtraCharge $record): string => ExtraChargeResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->url(fn (ExtraCharge $record): string => ExtraChargeResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<int, mixed>
     */
    private static function columns(): array
    {
        return [
            TextColumn::make('title')
                ->label(__('admin.extra_charges.fields.title'))
                ->searchable(),
            TextColumn::make('type.name')
                ->label(__('admin.extra_charges.fields.extra_charge_type')),
            TextColumn::make('property.name')
                ->label(__('admin.extra_charges.fields.property'))
                ->state(fn (ExtraCharge $record): string => $record->property?->displayName() ?? '—'),
            TextColumn::make('total_amount')
                ->label(__('admin.extra_charges.fields.total_amount'))
                ->state(fn (ExtraCharge $record): string => EuMoneyFormatter::format($record->total_amount, $record->currency)),
            TextColumn::make('status')
                ->label(__('admin.extra_charges.fields.status'))
                ->state(fn (ExtraCharge $record): string => $record->statusLabel())
                ->badge(),
            IconColumn::make('is_recurring')
                ->label(__('admin.extra_charges.fields.is_recurring'))
                ->boolean(),
            TextColumn::make('invoice.invoice_number')
                ->label(__('admin.extra_charges.fields.invoice'))
                ->url(fn (ExtraCharge $record): ?string => $record->invoice_id !== null
                    ? InvoiceResource::getUrl('view', ['record' => $record->invoice_id])
                    : null),
        ];
    }

    private function createChargeAction(string $name, bool $recurring): Action
    {
        return Action::make($name)
            ->label($recurring ? __('admin.extra_charges.actions.add_recurring_service') : __('admin.extra_charges.actions.add_one_time_charge'))
            ->authorize(fn (): bool => ExtraChargeResource::canCreate())
            ->schema(fn (): array => ExtraChargeForm::components(
                tenantId: $this->tenant()->id,
                recurringDefault: $recurring,
                organizationId: $this->tenant()->organization_id,
            ))
            ->action(function (array $data, CreateExtraChargeAction $action) use ($recurring): void {
                $actor = self::currentUser();

                if (! $actor instanceof User) {
                    abort(403);
                }

                $tenant = $this->tenant();
                $organization = Organization::query()->findOrFail($tenant->organization_id);

                $action->handle($actor, $organization, [
                    ...$data,
                    'tenant_id' => $tenant->id,
                    'is_recurring' => $recurring,
                ]);

                Notification::make()
                    ->success()
                    ->title(__('admin.extra_charges.messages.created'))
                    ->send();
            });
    }

    private function tenant(): User
    {
        /** @var User $tenant */
        $tenant = $this->getOwnerRecord();

        return $tenant;
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
