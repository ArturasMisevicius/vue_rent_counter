<?php

namespace App\Filament\Support\Admin\Invoices;

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Carbon;

class InvoiceLineItemCalculator
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(Property $property, Carbon $periodStart, Carbon $periodEnd): array
    {
        $property->loadMissing([
            'serviceConfigurations' => fn ($query) => $query
                ->select([
                    'id',
                    'organization_id',
                    'property_id',
                    'utility_service_id',
                    'pricing_model',
                    'rate_schedule',
                    'distribution_method',
                    'effective_from',
                    'effective_until',
                    'tariff_id',
                    'provider_id',
                    'is_shared_service',
                ])
                ->with([
                    'utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge',
                    'tariff:id,provider_id,configuration',
                ])
                ->where('is_active', true),
        ]);

        return $property->serviceConfigurations
            ->filter(fn (ServiceConfiguration $configuration): bool => $this->isEffectiveFor($configuration, $periodEnd))
            ->map(function (ServiceConfiguration $configuration) use ($property, $periodStart, $periodEnd): array {
                $rate = $this->resolveRate($configuration);
                $quantity = $this->resolveQuantity($property, $configuration, $periodStart, $periodEnd);
                $baseFee = (float) ($configuration->rate_schedule['base_fee'] ?? 0);
                $total = round(($quantity * $rate) + $baseFee, 2);

                return [
                    'utility_service_id' => $configuration->utility_service_id,
                    'description' => (string) ($configuration->utilityService?->name ?? 'Service charge'),
                    'quantity' => round($quantity, 2),
                    'unit' => (string) ($configuration->utilityService?->unit_of_measurement ?? ''),
                    'unit_price' => round($rate, 4),
                    'total' => $total,
                    'consumption' => round($quantity, 3),
                    'rate' => round($rate, 4),
                    'meter_reading_snapshot' => $this->resolveMeterSnapshot($property, $configuration, $periodStart, $periodEnd),
                ];
            })
            ->values()
            ->all();
    }

    private function isEffectiveFor(ServiceConfiguration $configuration, Carbon $periodEnd): bool
    {
        $effectiveFrom = Carbon::parse($configuration->effective_from);
        $effectiveUntil = $configuration->effective_until ? Carbon::parse($configuration->effective_until) : null;

        return $effectiveFrom->lte($periodEnd)
            && ($effectiveUntil === null || $effectiveUntil->gte($periodEnd));
    }

    private function resolveRate(ServiceConfiguration $configuration): float
    {
        return (float) ($configuration->rate_schedule['unit_rate']
            ?? $configuration->tariff?->configuration['rate']
            ?? 0);
    }

    private function resolveQuantity(
        Property $property,
        ServiceConfiguration $configuration,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): float {
        if (! $configuration->requiresConsumptionData()) {
            return 1.0;
        }

        $serviceType = $configuration->utilityService?->service_type_bridge?->value;

        if ($serviceType === null) {
            return 0.0;
        }

        $meter = Meter::query()
            ->select(['id', 'property_id', 'type'])
            ->where('property_id', $property->id)
            ->where('type', $serviceType)
            ->orderBy('id')
            ->first();

        if (! $meter) {
            return 0.0;
        }

        $previous = MeterReading::query()
            ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<', $periodStart)
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        $current = MeterReading::query()
            ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<=', $periodEnd)
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        if (! $previous || ! $current) {
            return 0.0;
        }

        return max(0, (float) $current->reading_value - (float) $previous->reading_value);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMeterSnapshot(
        Property $property,
        ServiceConfiguration $configuration,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): ?array {
        if (! $configuration->requiresConsumptionData()) {
            return null;
        }

        $serviceType = $configuration->utilityService?->service_type_bridge?->value;

        if ($serviceType === null) {
            return null;
        }

        $meter = Meter::query()
            ->select(['id', 'property_id', 'type', 'name'])
            ->where('property_id', $property->id)
            ->where('type', $serviceType)
            ->orderBy('id')
            ->first();

        if (! $meter) {
            return null;
        }

        $startReading = MeterReading::query()
            ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<', $periodStart)
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        $endReading = MeterReading::query()
            ->select(['id', 'meter_id', 'reading_value', 'reading_date'])
            ->where('meter_id', $meter->id)
            ->whereDate('reading_date', '<=', $periodEnd)
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->first();

        if (! $startReading || ! $endReading) {
            return null;
        }

        return [
            'meter_id' => $meter->id,
            'meter_name' => $meter->name,
            'start' => [
                'id' => $startReading->id,
                'value' => (float) $startReading->reading_value,
                'date' => $startReading->reading_date?->toDateString(),
            ],
            'end' => [
                'id' => $endReading->id,
                'value' => (float) $endReading->reading_value,
                'date' => $endReading->reading_date?->toDateString(),
            ],
        ];
    }
}
