<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\ServiceConfiguration;

final class TariffResolver
{
    /**
     * @return array{
     *     type: string,
     *     unit_rate: string,
     *     base_fee: string,
     *     zones: array<int, array{id: string, rate: string, start: string|null, end: string|null}>
     * }
     */
    public function resolve(ServiceConfiguration $configuration): array
    {
        $rateSchedule = is_array($configuration->rate_schedule) ? $configuration->rate_schedule : [];
        $tariffConfiguration = is_array($configuration->tariff?->configuration) ? $configuration->tariff?->configuration : [];
        $overrides = is_array($configuration->configuration_overrides) ? $configuration->configuration_overrides : [];
        $zones = $rateSchedule['zones'] ?? $tariffConfiguration['zones'] ?? $overrides['zones'] ?? [];

        return [
            'type' => (string) ($rateSchedule['type']
                ?? $tariffConfiguration['type']
                ?? $configuration->pricing_model?->value
                ?? 'flat'),
            'unit_rate' => $this->normalizeDecimal(
                $overrides['unit_rate']
                    ?? $rateSchedule['unit_rate']
                    ?? $tariffConfiguration['rate']
                    ?? 0,
                4,
            ),
            'base_fee' => $this->normalizeDecimal(
                $overrides['base_fee']
                    ?? $rateSchedule['base_fee']
                    ?? $tariffConfiguration['base_fee']
                    ?? 0,
                2,
            ),
            'zones' => $this->normalizeZones(is_array($zones) ? $zones : []),
        ];
    }

    /**
     * @param  array<int, mixed>  $zones
     * @return array<int, array{id: string, rate: string, start: string|null, end: string|null}>
     */
    private function normalizeZones(array $zones): array
    {
        return array_values(array_map(function (mixed $zone): array {
            $resolvedZone = is_array($zone) ? $zone : [];

            return [
                'id' => (string) ($resolvedZone['id'] ?? ''),
                'rate' => $this->normalizeDecimal($resolvedZone['rate'] ?? 0, 4),
                'start' => isset($resolvedZone['start']) ? (string) $resolvedZone['start'] : null,
                'end' => isset($resolvedZone['end']) ? (string) $resolvedZone['end'] : null,
            ];
        }, $zones));
    }

    private function normalizeDecimal(string|int|float $value, int $scale): string
    {
        if (is_int($value)) {
            return bcadd((string) $value, '0', $scale);
        }

        if (is_float($value)) {
            return bcadd(sprintf("%.{$scale}F", $value), '0', $scale);
        }

        return bcadd($value !== '' ? $value : '0', '0', $scale);
    }
}
