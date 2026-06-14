<?php

declare(strict_types=1);

namespace App\Filament\Actions\Tenant\Readings;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Billing\TenantReadingsSubmittedForInvoiceNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class CompleteReadingRequestInvoiceAction
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
        private readonly NotificationPreferenceService $notificationPreferenceService,
        private readonly ManagerPermissionService $managerPermissionService,
    ) {}

    /**
     * @param  list<MeterReading>  $readings
     */
    public function handle(User $tenant, string|int|null $invoiceId, array $readings): ?Invoice
    {
        $resolvedInvoiceId = $this->normalizeInvoiceId($invoiceId);

        if ($resolvedInvoiceId === null) {
            return null;
        }

        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null || $workspace->propertyId === null) {
            return null;
        }

        $invoice = DB::transaction(function () use ($readings, $resolvedInvoiceId, $tenant, $workspace): ?Invoice {
            $invoice = $this->readingRequestInvoiceQuery(
                invoiceId: $resolvedInvoiceId,
                organizationId: $workspace->organizationId,
                propertyId: $workspace->propertyId,
                tenantId: $tenant->id,
            )
                ->lockForUpdate()
                ->first();

            if (! $invoice instanceof Invoice) {
                return null;
            }

            $readingIds = $this->submittedReadingIds($invoice, $tenant, $readings);

            if ($readingIds === []) {
                return $invoice;
            }

            $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];
            $existingIds = collect($metadata['submitted_meter_reading_ids'] ?? [])
                ->filter(fn (mixed $value): bool => is_numeric($value))
                ->map(fn (mixed $value): int => (int) $value)
                ->all();
            $submittedReadingIds = collect($existingIds)
                ->merge($readingIds)
                ->unique()
                ->values()
                ->all();

            $invoice->forceFill([
                'approval_status' => 'readings_submitted',
                'approval_metadata' => [
                    ...$metadata,
                    'workflow' => $metadata['workflow'] ?? 'meter_reading_request',
                    'meter_readings_submitted_at' => now()->toISOString(),
                    'submitted_by_tenant_user_id' => $tenant->id,
                    'submitted_meter_reading_ids' => $submittedReadingIds,
                    'submitted_reading_count' => count($submittedReadingIds),
                ],
            ])->save();

            return $invoice->fresh(['organization:id,name,owner_user_id']);
        });

        if (! $invoice instanceof Invoice) {
            return null;
        }

        $readingCount = (int) ($invoice->approval_metadata['submitted_reading_count'] ?? 0);

        if ($readingCount > 0 && $this->notificationPreferenceService->enabledForOrganization($invoice->organization, NotificationPreferenceService::TENANT_SUBMITS_READING)) {
            Notification::send(
                $this->notificationRecipients($invoice),
                new TenantReadingsSubmittedForInvoiceNotification($invoice, $tenant, $readingCount),
            );
        }

        return $invoice;
    }

    private function normalizeInvoiceId(string|int|null $invoiceId): ?int
    {
        if ($invoiceId === null) {
            return null;
        }

        $invoiceId = trim((string) $invoiceId);

        if ($invoiceId === '' || ! ctype_digit($invoiceId)) {
            return null;
        }

        $resolvedInvoiceId = (int) $invoiceId;

        return $resolvedInvoiceId > 0 ? $resolvedInvoiceId : null;
    }

    /**
     * @return Builder<Invoice>
     */
    private function readingRequestInvoiceQuery(int $invoiceId, int $organizationId, int $propertyId, int $tenantId): Builder
    {
        return Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'approval_status',
                'automation_level',
                'approval_metadata',
            ])
            ->whereKey($invoiceId)
            ->forOrganization($organizationId)
            ->forProperty($propertyId)
            ->forTenant($tenantId)
            ->where('status', InvoiceStatus::DRAFT->value)
            ->where('automation_level', 'reading_request')
            ->where('approval_status', 'pending');
    }

    /**
     * @param  list<MeterReading>  $readings
     * @return list<int>
     */
    private function submittedReadingIds(Invoice $invoice, User $tenant, array $readings): array
    {
        return collect($readings)
            ->filter(fn (mixed $reading): bool => $reading instanceof MeterReading
                && $reading->id !== null
                && (int) $reading->organization_id === (int) $invoice->organization_id
                && (int) $reading->property_id === (int) $invoice->property_id
                && (int) $reading->submitted_by_user_id === (int) $tenant->id)
            ->map(fn (MeterReading $reading): int => (int) $reading->id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return EloquentCollection<int, User>
     */
    private function notificationRecipients(Invoice $invoice): EloquentCollection
    {
        $organization = $invoice->organization;

        if (! $organization instanceof Organization) {
            return new EloquentCollection;
        }

        $users = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($organization->id)
            ->active()
            ->whereIn('role', [UserRole::ADMIN->value, UserRole::MANAGER->value])
            ->get();

        if ($organization->owner_user_id !== null && ! $users->contains('id', $organization->owner_user_id)) {
            $owner = User::query()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                ->active()
                ->find($organization->owner_user_id);

            if ($owner instanceof User) {
                $users->push($owner);
            }
        }

        return new EloquentCollection(
            $users
                ->filter(fn (User $user): bool => $this->canReceiveNotification($user, $organization))
                ->unique('id')
                ->values()
                ->all(),
        );
    }

    private function canReceiveNotification(User $user, Organization $organization): bool
    {
        if ($user->isAdmin() || $organization->owner_user_id === $user->id) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        return $this->managerPermissionService->can($user, $organization, 'invoices', 'edit');
    }
}
