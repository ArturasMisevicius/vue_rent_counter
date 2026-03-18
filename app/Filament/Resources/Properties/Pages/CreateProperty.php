<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Enums\SubscriptionAccessMode;
use App\Filament\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionEnforcementMessage;
use App\Services\SubscriptionChecker;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(PropertyResource::canViewAny(), 403);
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

        if (! $state->blocksCreation('properties')) {
            return $data;
        }

        $message = app(SubscriptionEnforcementMessage::class)->forResource('properties', $state);

        Notification::make()
            ->danger()
            ->persistent()
            ->title($message['title'])
            ->body($message['body'])
            ->actions([
                Action::make('upgradePlan')
                    ->label(
                        $state->mode === SubscriptionAccessMode::LIMIT_BLOCKED
                            ? __('subscriptions.actions.upgrade_plan')
                            : $message['action_label'],
                    )
                    ->button()
                    ->url($message['action_url']),
            ])
            ->send();

        $this->halt();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_if($organization === null, 403);

        return app(CreatePropertyAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return PropertyResource::getUrl('index');
    }
}
