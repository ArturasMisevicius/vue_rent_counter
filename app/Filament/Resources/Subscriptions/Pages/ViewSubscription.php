<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\SubscriptionPlan;
use App\Filament\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpdateSubscriptionExpiryAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Http\Requests\Superadmin\Subscriptions\UpgradeSubscriptionPlanRequest;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
        return 'Subscription Overview';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('extendExpiry')
                ->label('Extend Expiry')
                ->authorize(fn (): bool => auth()->user()?->can('extend', $this->record) ?? false)
                ->schema([
                    DatePicker::make('expires_at')
                        ->label('Expires At')
                        ->required()
                        ->default($this->record->expires_at?->toDateString()),
                ])
                ->action(function (array $data, UpdateSubscriptionExpiryAction $updateSubscriptionExpiryAction): void {
                    $updateSubscriptionExpiryAction->handle($this->record, $data);
                    $this->refreshRecord();

                    Notification::make()
                        ->title('Subscription expiry updated')
                        ->success()
                        ->send();
                }),
            Action::make('upgradePlan')
                ->label('Upgrade Plan')
                ->authorize(fn (): bool => auth()->user()?->can('upgrade', $this->record) ?? false)
                ->schema([
                    Select::make('plan')
                        ->label('Plan')
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
                        ->title('Subscription plan updated')
                        ->success()
                        ->send();
                }),
            Action::make('suspendSubscription')
                ->label('Suspend')
                ->color('danger')
                ->authorize(fn (): bool => auth()->user()?->can('suspend', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Suspended subscriptions immediately stop access for organization billing features.')
                ->action(function (SuspendSubscriptionAction $suspendSubscriptionAction): void {
                    $suspendSubscriptionAction->handle($this->record);
                    $this->refreshRecord();

                    Notification::make()
                        ->title('Subscription suspended')
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
}
