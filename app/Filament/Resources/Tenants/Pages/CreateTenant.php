<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Actions\Admin\Tenants\CreateTenantWithAssignment;
use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\Tenants\Pages\Concerns\InteractsWithTenantLeaseAgreementFormData;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionEnforcementMessage;
use App\Filament\Support\Tenants\TenantCreationResult;
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
    use InteractsWithTenantLeaseAgreementFormData;

    protected static string $resource = TenantResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:tenants,create';

    protected static bool $canCreateAnother = true;

    private ?TenantCreationResult $creationResult = null;

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('tenant.create'),
        ];
    }

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

        [$data, $this->leaseAgreementFormData] = $this->extractLeaseAgreementFormData($data);

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

        $this->creationResult = app(CreateTenantWithAssignment::class)->handle($actor, $data, $organization);

        return $this->creationResult->tenant;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof User) {
            $this->syncTenantLeaseAgreement($this->record);
        }
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
            ->label(__('admin.tenants.actions.create_tenant'));
    }

    protected function getCreatedNotification(): ?Notification
    {
        $result = $this->creationResult;
        $invitation = $result?->invitation ?? (
            $this->record instanceof User
                ? $this->record->latestTenantInvitationRecord()
                : null
        );

        $readiness = $result?->billingReadiness;
        $nextSteps = collect($result?->nextSteps ?? [])
            ->map(fn (string $step): string => __("admin.tenants.next_steps.{$step}"))
            ->implode("\n");
        $body = collect([
            $invitation === null
                ? __('admin.tenants.messages.invitation_not_sent')
                : __('admin.tenants.messages.invitation_sent', [
                    'email' => (string) $this->record?->email,
                ]),
            $readiness === null
                ? null
                : __('admin.tenants.billing_readiness.status_label', [
                    'status' => $readiness->status->getLabel(),
                ]),
            $nextSteps !== '' ? __('admin.tenants.messages.next_steps_summary', ['steps' => $nextSteps]) : null,
        ])
            ->filter()
            ->implode("\n\n");

        return Notification::make()
            ->success()
            ->title(__('admin.tenants.messages.tenant_created'))
            ->body($body)
            ->actions([
                Action::make('openTenantProfile')
                    ->label(__('admin.tenants.actions.view_profile'))
                    ->button()
                    ->url($this->getRedirectUrl()),
            ]);
    }
}
