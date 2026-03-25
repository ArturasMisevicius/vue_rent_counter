<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\SubscriptionPlan;
use App\Filament\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpdateSubscriptionExpiryAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Http\Requests\Superadmin\Subscriptions\UpgradeSubscriptionPlanRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            SubscriptionResource::getUrl('index') => SubscriptionResource::getPluralModelLabel(),
            (string) $this->record->organization?->name,
        ];
    }

    public function getTitle(): string
    {
        return __('superadmin.subscriptions_resource.sections.overview');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('extendExpiry')
                ->label(__('superadmin.subscriptions_resource.actions.extend_expiry'))
                ->authorize(fn (): bool => $this->currentUser()?->can('extend', $this->record) ?? false)
                ->schema([
                    DatePicker::make('expires_at')
                        ->label(__('superadmin.subscriptions_resource.fields.expires_at'))
                        ->required()
                        ->default($this->record->expires_at?->toDateString()),
                ])
                ->action(function (array $data, UpdateSubscriptionExpiryAction $updateSubscriptionExpiryAction): void {
                    $updateSubscriptionExpiryAction->handle($this->record, $data);
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.subscriptions_resource.messages.expiry_updated'))
                        ->success()
                        ->send();
                }),
            Action::make('upgradePlan')
                ->label(__('superadmin.subscriptions_resource.actions.upgrade_plan'))
                ->authorize(fn (): bool => $this->currentUser()?->can('upgrade', $this->record) ?? false)
                ->schema([
                    Select::make('plan')
                        ->label(__('superadmin.subscriptions_resource.fields.plan'))
                        ->options(SubscriptionPlan::options())
                        ->required()
                        ->default($this->record->plan?->value),
                ])
                ->action(function (array $data, UpgradeSubscriptionPlanAction $upgradeSubscriptionPlanAction): void {
                    /** @var UpgradeSubscriptionPlanRequest $request */
                    $request = app(UpgradeSubscriptionPlanRequest::class);
                    $validated = $request->validatePayload($data);

                    $upgradeSubscriptionPlanAction->handle($this->record, SubscriptionPlan::from($validated['plan']));
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.subscriptions_resource.messages.plan_updated'))
                        ->success()
                        ->send();
                }),
            Action::make('suspendSubscription')
                ->label(__('superadmin.subscriptions_resource.actions.suspend'))
                ->color('danger')
                ->authorize(fn (): bool => $this->currentUser()?->can('suspend', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(__('superadmin.subscriptions_resource.modals.suspend_now_description'))
                ->action(function (SuspendSubscriptionAction $suspendSubscriptionAction): void {
                    $suspendSubscriptionAction->handle($this->record);
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.subscriptions_resource.messages.suspended'))
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }

    private function refreshRecord(): void
    {
        $this->record = SubscriptionResource::getEloquentQuery()->findOrFail($this->record->getKey());
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
