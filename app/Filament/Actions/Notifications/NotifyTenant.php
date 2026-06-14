<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Filament\Support\Notifications\DomainNotificationContentFactory;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

final class NotifyTenant
{
    public function __construct(
        private readonly DomainNotificationContentFactory $contentFactory,
        private readonly DispatchDomainNotification $dispatchDomainNotification,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        User $tenant,
        string $type,
        Model $subject,
        array $data = [],
        ?User $actor = null,
    ): ?DatabaseNotification {
        if (
            ! $tenant->isTenant()
            || ! $this->subjectBelongsToTenant($tenant, $subject)
            || $this->invoiceHasConfigurationErrors($subject)
        ) {
            return null;
        }

        $content = $this->contentFactory->make($type, $subject, $data);

        return $this->dispatchDomainNotification->handle(
            recipient: $tenant,
            content: $content,
            organization: is_numeric($tenant->organization_id) ? (int) $tenant->organization_id : null,
            subject: $subject,
            actor: $actor,
        );
    }

    private function subjectBelongsToTenant(User $tenant, Model $subject): bool
    {
        if ($subject instanceof Invoice) {
            return (int) $subject->tenant_user_id === (int) $tenant->id
                && (int) $subject->organization_id === (int) $tenant->organization_id;
        }

        if ($subject instanceof MeterReading) {
            return (int) $subject->submitted_by_user_id === (int) $tenant->id
                && (int) $subject->organization_id === (int) $tenant->organization_id;
        }

        if ($subject instanceof OrganizationInvitation) {
            return (int) $subject->tenant_id === (int) $tenant->id
                && (int) $subject->organization_id === (int) $tenant->organization_id;
        }

        $organizationId = $subject->getAttribute('organization_id');

        return ! is_numeric($organizationId) || (int) $organizationId === (int) $tenant->organization_id;
    }

    private function invoiceHasConfigurationErrors(Model $subject): bool
    {
        if (! $subject instanceof Invoice) {
            return false;
        }

        $approvalMetadata = is_array($subject->approval_metadata) ? $subject->approval_metadata : [];
        $snapshotData = is_array($subject->snapshot_data) ? $subject->snapshot_data : [];

        return (bool) data_get($approvalMetadata, 'configuration_errors')
            || (bool) data_get($snapshotData, 'configuration_errors')
            || (bool) data_get($snapshotData, 'service_configuration_errors');
    }
}
