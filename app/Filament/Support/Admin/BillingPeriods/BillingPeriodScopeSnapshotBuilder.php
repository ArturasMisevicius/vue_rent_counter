<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingPeriods;

use App\Enums\MeterType;
use App\Models\BillingPeriod;
use App\Models\Meter;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class BillingPeriodScopeSnapshotBuilder
{
    /**
     * @return array{
     *     billing_period: array{id: int, name: string, starts_at: string, ends_at: string, reading_submission_deadline: string|null, invoice_generation_date: string|null, payment_due_date: string|null},
     *     assignments: list<array{
     *         assignment_id: int,
     *         tenant: array{id: int|null, name: string},
     *         property: array{id: int|null, name: string},
     *         meters: list<array{id: int, name: string, identifier: string, type: string, unit: string}>,
     *         services: list<array{id: int, name: string, pricing_model: string|null, tariff: array{id: int|null, name: string|null}}>
     *     }>,
     *     totals: array{assignments: int, tenants: int, properties: int, meters: int, services: int, tariffs: int}
     * }
     */
    public function handle(BillingPeriod $billingPeriod): array
    {
        $periodStart = CarbonImmutable::parse((string) $billingPeriod->starts_at)->startOfDay();
        $periodEnd = CarbonImmutable::parse((string) $billingPeriod->ends_at)->endOfDay();

        $assignments = PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'assigned_at',
                'unassigned_at',
                'billing_start_date',
                'billing_end_date',
            ])
            ->forOrganization((int) $billingPeriod->organization_id)
            ->activeDuring($periodStart, $periodEnd)
            ->whereHas(
                'tenant',
                fn (Builder $tenantQuery): Builder => $tenantQuery->tenants()->active(),
            )
            ->whereHas(
                'property.meters',
                fn (Builder $meterQuery): Builder => $meterQuery->active(),
            )
            ->with([
                'tenant:id,organization_id,name,email,role,status,locale',
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name',
                'property.meters' => fn (Builder $meterQuery): Builder => $meterQuery
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->active()
                    ->ordered(),
                'property.serviceConfigurations' => fn (Builder $configurationQuery): Builder => $configurationQuery
                    ->activeOn($periodEnd)
                    ->with([
                        'utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge,description',
                        'tariff:id,provider_id,name,configuration',
                    ])
                    ->ordered(),
            ])
            ->latestAssignedFirst()
            ->get();

        $rows = $assignments
            ->map(fn (PropertyAssignment $assignment): array => $this->assignmentRow($assignment))
            ->values()
            ->all();

        return [
            'billing_period' => [
                'id' => (int) $billingPeriod->id,
                'name' => (string) $billingPeriod->name,
                'starts_at' => $billingPeriod->starts_at?->toDateString() ?? '',
                'ends_at' => $billingPeriod->ends_at?->toDateString() ?? '',
                'reading_submission_deadline' => $billingPeriod->reading_submission_deadline?->toDateString(),
                'invoice_generation_date' => $billingPeriod->invoice_generation_date?->toDateString(),
                'payment_due_date' => $billingPeriod->payment_due_date?->toDateString(),
            ],
            'assignments' => $rows,
            'totals' => [
                'assignments' => count($rows),
                'tenants' => collect($rows)->pluck('tenant.id')->filter()->unique()->count(),
                'properties' => collect($rows)->pluck('property.id')->filter()->unique()->count(),
                'meters' => collect($rows)->pluck('meters')->flatten(1)->pluck('id')->filter()->unique()->count(),
                'services' => collect($rows)->pluck('services')->flatten(1)->pluck('id')->filter()->unique()->count(),
                'tariffs' => collect($rows)->pluck('services')->flatten(1)->pluck('tariff.id')->filter()->unique()->count(),
            ],
        ];
    }

    /**
     * @return array{
     *     assignment_id: int,
     *     tenant: array{id: int|null, name: string},
     *     property: array{id: int|null, name: string},
     *     meters: list<array{id: int, name: string, identifier: string, type: string, unit: string}>,
     *     services: list<array{id: int, name: string, pricing_model: string|null, tariff: array{id: int|null, name: string|null}}>
     * }
     */
    private function assignmentRow(PropertyAssignment $assignment): array
    {
        return [
            'assignment_id' => (int) $assignment->id,
            'tenant' => [
                'id' => $assignment->tenant_user_id,
                'name' => (string) ($assignment->tenant?->name ?? ''),
            ],
            'property' => [
                'id' => $assignment->property_id,
                'name' => (string) ($assignment->property?->displayName() ?? ''),
            ],
            'meters' => $assignment->property?->meters
                ? $assignment->property->meters
                    ->map(fn (Meter $meter): array => $this->meterRow($meter))
                    ->values()
                    ->all()
                : [],
            'services' => $assignment->property?->serviceConfigurations
                ? $assignment->property->serviceConfigurations
                    ->map(fn (ServiceConfiguration $configuration): array => $this->serviceRow($configuration))
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * @return array{id: int, name: string, identifier: string, type: string, unit: string}
     */
    private function meterRow(Meter $meter): array
    {
        return [
            'id' => (int) $meter->id,
            'name' => $meter->displayName(),
            'identifier' => (string) $meter->identifier,
            'type' => $meter->type instanceof MeterType ? $meter->type->value : (string) $meter->type,
            'unit' => (string) $meter->unit,
        ];
    }

    /**
     * @return array{id: int, name: string, pricing_model: string|null, tariff: array{id: int|null, name: string|null}}
     */
    private function serviceRow(ServiceConfiguration $configuration): array
    {
        return [
            'id' => (int) $configuration->id,
            'name' => (string) (
                $configuration->tenant_visible_name
                    ?: $configuration->service_name
                    ?: $configuration->utilityService?->name
                    ?: ''
            ),
            'pricing_model' => $configuration->pricing_model?->value,
            'tariff' => [
                'id' => $configuration->tariff_id,
                'name' => $configuration->tariff?->name,
            ],
        ];
    }
}
