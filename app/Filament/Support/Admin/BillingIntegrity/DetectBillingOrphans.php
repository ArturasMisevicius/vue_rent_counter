<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingIntegrity;

use App\Models\Attachment;
use App\Models\ExtraCharge;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\MeterReading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DetectBillingOrphans
{
    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    public function forOrganization(int $organizationId): Collection
    {
        return collect()
            ->merge($this->orphanReadings($organizationId))
            ->merge($this->orphanInvoiceItems($organizationId))
            ->merge($this->chargesWithoutTenantOrProperty($organizationId))
            ->merge($this->documentsWithoutAttachable($organizationId))
            ->merge($this->paymentsWithoutInvoice($organizationId))
            ->values();
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function orphanReadings(int $organizationId): Collection
    {
        $ids = MeterReading::query()
            ->select(['id', 'organization_id', 'property_id', 'meter_id'])
            ->forOrganization($organizationId)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('property_id')
                    ->orWhereNull('meter_id');
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $this->issueIfAny(
            organizationId: $organizationId,
            problemType: 'orphan_readings',
            entityType: 'meter_reading',
            entityIds: $ids,
            recommendedAction: 'review_manually',
        );
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function orphanInvoiceItems(int $organizationId): Collection
    {
        $ids = InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id', 'service_configuration_id', 'utility_service_id'])
            ->whereHas('invoice', fn (Builder $query): Builder => $query->forOrganization($organizationId))
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('source_type')
                    ->orWhere(function (Builder $sourceQuery): void {
                        $sourceQuery
                            ->whereNull('source_id')
                            ->whereNull('service_configuration_id')
                            ->whereNull('utility_service_id');
                    });
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $this->issueIfAny(
            organizationId: $organizationId,
            problemType: 'orphan_invoice_items',
            entityType: 'invoice_item',
            entityIds: $ids,
            recommendedAction: 'void',
        );
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function chargesWithoutTenantOrProperty(int $organizationId): Collection
    {
        $ids = ExtraCharge::query()
            ->select(['id', 'organization_id', 'tenant_id', 'property_id'])
            ->where('organization_id', $organizationId)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('tenant_id')
                    ->orWhereNull('property_id');
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $this->issueIfAny(
            organizationId: $organizationId,
            problemType: 'charges_without_tenant_or_property',
            entityType: 'extra_charge',
            entityIds: $ids,
            recommendedAction: 'review_manually',
        );
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function documentsWithoutAttachable(int $organizationId): Collection
    {
        $ids = Attachment::query()
            ->select(['id', 'organization_id', 'attachable_type', 'attachable_id'])
            ->forOrganization($organizationId)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('attachable_type')
                    ->orWhereNull('attachable_id');
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $this->issueIfAny(
            organizationId: $organizationId,
            problemType: 'documents_without_attachable',
            entityType: 'attachment',
            entityIds: $ids,
            recommendedAction: 'archive',
        );
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function paymentsWithoutInvoice(int $organizationId): Collection
    {
        $ids = InvoicePayment::query()
            ->select(['id', 'organization_id', 'invoice_id'])
            ->forOrganizationValue($organizationId)
            ->whereDoesntHave('invoice')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        return $this->issueIfAny(
            organizationId: $organizationId,
            problemType: 'payments_without_invoice',
            entityType: 'invoice_payment',
            entityIds: $ids,
            recommendedAction: 'review_manually',
        );
    }

    /**
     * @param  list<int>  $entityIds
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function issueIfAny(
        int $organizationId,
        string $problemType,
        string $entityType,
        array $entityIds,
        string $recommendedAction,
    ): Collection {
        if ($entityIds === []) {
            return collect();
        }

        return collect([
            new BillingIntegrityIssue(
                problemType: $problemType,
                entityType: $entityType,
                entityIds: $entityIds,
                severity: 'warning',
                recommendedAction: $recommendedAction,
                organizationId: $organizationId,
            ),
        ]);
    }
}
