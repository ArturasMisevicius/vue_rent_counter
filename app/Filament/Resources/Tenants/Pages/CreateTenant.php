<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Actions\Admin\Tenants\CreateTenantAction;
use App\Filament\Resources\Tenants\TenantResource;
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

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:tenants,create';

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('admin.tenants.titles.new');
    }

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
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isSuperadmin() && app(OrganizationContext::class)->currentOrganizationId() === null) {
            return $data;
        }

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

        $organization = app(OrganizationContext::class)->currentOrganization();

        if ($organization === null) {
            abort_if(! $actor->isSuperadmin(), 403);

            $organizationId = (int) ($data['organization_id'] ?? 0);
            $organization = Organization::query()
                ->select(['id', 'name', 'slug', 'status', 'owner_user_id', 'created_at', 'updated_at'])
                ->findOrFail($organizationId);
        }

        unset($data['organization_id']);

        return app(CreateTenantAction::class)->handle($actor, $data, $organization);
    }

    protected function getRedirectUrl(): string
    {
        return TenantResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('admin.tenants.actions.save_tenant'));
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.tenants.messages.tenant_created'))
            ->body(__('admin.tenants.messages.invitation_sent', [
                'email' => (string) $this->record?->email,
            ]));
    }
}
