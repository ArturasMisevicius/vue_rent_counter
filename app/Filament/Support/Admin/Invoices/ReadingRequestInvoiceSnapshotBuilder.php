<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Invoices;

use App\Enums\MeterType;
use App\Enums\ServiceType;
use App\Models\BillingPeriod;
use App\Models\Meter;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ReadingRequestInvoiceSnapshotBuilder
{
    /**
     * @return array{
     *     status: string,
     *     tenant: array{id: int|null, name: string},
     *     property: array{id: int|null, name: string},
     *     period: array{id: int|null, name: string, starts_at: string, ends_at: string},
     *     deadline: string,
     *     linked_meters: list<array<string, mixed>>,
     *     expected_services: list<array<string, mixed>>,
     *     required_inputs: list<array<string, mixed>>
     * }
     */
    public function handle(
        PropertyAssignment $assignment,
        BillingPeriod $billingPeriod,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        string $deadline,
    ): array {
        $this->loadRequestRelations($assignment, $periodEnd);

        $property = $assignment->property;
        $meters = $property?->meters instanceof Collection ? $property->meters->values() : collect();
        $services = $property?->serviceConfigurations instanceof Collection
            ? $property->serviceConfigurations->values()
            : collect();
        $serviceRows = $services
            ->map(fn (ServiceConfiguration $configuration): array => $this->serviceRow($configuration, $meters))
            ->values()
            ->all();

        return [
            'status' => 'waiting_for_readings',
            'tenant' => [
                'id' => $assignment->tenant_user_id,
                'name' => (string) ($assignment->tenant?->name ?? ''),
            ],
            'property' => [
                'id' => $assignment->property_id,
                'name' => (string) ($property?->displayName() ?? ''),
            ],
            'period' => [
                'id' => $billingPeriod->id,
                'name' => (string) $billingPeriod->name,
                'starts_at' => $periodStart->toDateString(),
                'ends_at' => $periodEnd->toDateString(),
            ],
            'deadline' => $deadline,
            'linked_meters' => $meters
                ->map(fn (Meter $meter): array => $this->meterRow($meter))
                ->values()
                ->all(),
            'expected_services' => $serviceRows,
            'required_inputs' => $meters
                ->map(fn (Meter $meter): array => $this->requiredInputRow($meter, $services))
                ->values()
                ->all(),
        ];
    }

    private function loadRequestRelations(PropertyAssignment $assignment, CarbonInterface $periodEnd): void
    {
        $assignment->loadMissing([
            'tenant:id,organization_id,name,email,role,status,locale',
            'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
            'property.meters' => fn ($query) => $query
                ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                ->active()
                ->ordered(),
            'property.serviceConfigurations' => fn ($query) => $query
                ->activeOn($periodEnd)
                ->with(['utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge,description'])
                ->ordered(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function meterRow(Meter $meter): array
    {
        return [
            'id' => (int) $meter->id,
            'name' => $meter->displayName(),
            'identifier' => (string) $meter->identifier,
            'type' => $meter->type instanceof MeterType ? $meter->type->value : (string) $meter->type,
            'type_label' => $meter->type instanceof MeterType ? $meter->type->getLabel() : (string) $meter->type,
            'unit' => (string) $meter->unit,
            'status' => 'pending',
        ];
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return array<string, mixed>
     */
    private function serviceRow(ServiceConfiguration $configuration, Collection $meters): array
    {
        $serviceType = $this->serviceType($configuration);
        $compatibleMeterIds = $serviceType instanceof ServiceType
            ? $this->compatibleMeterIds($serviceType, $meters)
            : [];

        return [
            'id' => (int) $configuration->id,
            'name' => (string) (
                $configuration->tenant_visible_name
                    ?: $configuration->service_name
                    ?: $configuration->utilityService?->name
                    ?: ''
            ),
            'description' => (string) (
                $configuration->tenant_visible_description
                    ?: $configuration->invoice_description
                    ?: $configuration->utilityService?->description
                    ?: ''
            ),
            'service_type' => $serviceType?->value,
            'pricing_model' => $configuration->pricing_model?->value,
            'requires_reading' => $configuration->requiresConsumptionData(),
            'expected_meter_ids' => $compatibleMeterIds,
            'tenant_visible' => (bool) $configuration->tenant_visible,
        ];
    }

    /**
     * @param  Collection<int, ServiceConfiguration>  $services
     * @return array<string, mixed>
     */
    private function requiredInputRow(Meter $meter, Collection $services): array
    {
        return [
            'key' => 'meter:'.$meter->id.':reading',
            'type' => 'meter_reading',
            'meter_id' => (int) $meter->id,
            'meter_name' => $meter->displayName(),
            'meter_identifier' => (string) $meter->identifier,
            'unit' => (string) $meter->unit,
            'status' => 'pending',
            'required' => true,
            'expected_service_ids' => $services
                ->filter(fn (ServiceConfiguration $configuration): bool => $this->serviceMatchesMeter($configuration, $meter))
                ->map(fn (ServiceConfiguration $configuration): int => (int) $configuration->id)
                ->values()
                ->all(),
        ];
    }

    private function serviceMatchesMeter(ServiceConfiguration $configuration, Meter $meter): bool
    {
        $serviceType = $this->serviceType($configuration);

        if (! $serviceType instanceof ServiceType) {
            return false;
        }

        return in_array(
            $meter->type instanceof MeterType ? $meter->type->value : (string) $meter->type,
            collect($serviceType->compatibleMeterTypes())->map(fn (MeterType $type): string => $type->value)->all(),
            true,
        );
    }

    private function serviceType(ServiceConfiguration $configuration): ?ServiceType
    {
        if ($configuration->utilityService?->service_type_bridge instanceof ServiceType) {
            return $configuration->utilityService->service_type_bridge;
        }

        return $configuration->service_type instanceof ServiceType ? $configuration->service_type : null;
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return list<int>
     */
    private function compatibleMeterIds(ServiceType $serviceType, Collection $meters): array
    {
        $compatibleTypes = collect($serviceType->compatibleMeterTypes())
            ->map(fn (MeterType $type): string => $type->value)
            ->all();

        if ($compatibleTypes === []) {
            return [];
        }

        return $meters
            ->filter(fn (Meter $meter): bool => in_array(
                $meter->type instanceof MeterType ? $meter->type->value : (string) $meter->type,
                $compatibleTypes,
                true,
            ))
            ->map(fn (Meter $meter): int => (int) $meter->id)
            ->values()
            ->all();
    }
}
