<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Actions\Admin\Tenants\CreateTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionEnforcementMessage;
use App\Services\SubscriptionChecker;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(TenantResource::canViewAny(), 403);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_if($user === null, 403);

        $state = app(SubscriptionChecker::class)->accessState($user);

        if (! $state->blocksCreation('tenants')) {
            return $data;
        }

        $message = app(SubscriptionEnforcementMessage::class)->forResource('tenants', $state);

        Notification::make()
            ->danger()
            ->persistent()
            ->title($message['title'])
            ->body($message['body'])
            ->actions([
                Action::make('upgradePlan')
                    ->label(__('subscriptions.actions.upgrade_plan'))
                    ->button()
                    ->url($message['action_url']),
            ])
            ->send();

        $this->halt();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = app(OrganizationContext::class)->currentUser();

        abort_if($actor === null, 403);

        return app(CreateTenantAction::class)->handle($actor, $data);
    }

    protected function getRedirectUrl(): string
    {
        return TenantResource::getUrl('index');
    }
}
