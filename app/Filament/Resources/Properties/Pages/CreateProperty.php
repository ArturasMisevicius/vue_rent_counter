<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Enums\SubscriptionAccessMode;
use App\Filament\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionEnforcementMessage;
use App\Models\Organization;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:properties,create';

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('admin.properties.titles.new');
    }

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
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $isSuperadmin = $user->isSuperadmin();

        if ($isSuperadmin && app(OrganizationContext::class)->currentOrganizationId() === null) {
            return $data;
        }

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

        if ($organization === null) {
            $user = Auth::user();

            if (! $user instanceof User) {
                abort(403);
            }

            abort_if(! $user->isSuperadmin(), 403);

            $organizationId = (int) ($data['organization_id'] ?? 0);
            $organization = Organization::query()->findOrFail($organizationId);
        }

        unset($data['organization_id']);

        return app(CreatePropertyAction::class)->handle($organization, $data);
    }

    protected function getRedirectUrl(): string
    {
        return PropertyResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('admin.properties.actions.save_property'));
    }
}
