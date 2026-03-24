<?php

namespace App\Filament\Support\Superadmin\Integration;

use App\Enums\IntegrationHealthStatus;
use App\Filament\Support\Superadmin\SecurityViolations\SecurityViolationTablePresenter;
use App\Models\IntegrationHealthCheck;
use App\Models\SecurityViolation;
use Illuminate\Support\Collection;

class IntegrationHealthPageData
{
    /**
     * @return array{
     *     checks: Collection<int, array{
     *         id: int,
     *         label: string,
     *         key: string,
     *         status_label: string,
     *         status_badge_class: string,
     *         summary: string,
     *         response_time_label: string,
     *         checked_at_label: string,
     *         can_reset_circuit_breaker: bool
     *     }>,
     *     recentViolations: Collection<int, array{
     *         id: int,
     *         summary: string,
     *         type_label: string,
     *         severity_label: string,
     *         severity_badge_class: string,
     *         organization_name: string,
     *         source_label: string,
     *         ip_address_label: string,
     *         occurred_at_label: string
     *     }>
     * }
     */
    public function viewData(): array
    {
        return [
            'checks' => $this->checks(),
            'recentViolations' => $this->recentViolations(),
        ];
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     label: string,
     *     key: string,
     *     status_label: string,
     *     status_badge_class: string,
     *     summary: string,
     *     response_time_label: string,
     *     checked_at_label: string,
     *     can_reset_circuit_breaker: bool
     * }>
     */
    private function checks(): Collection
    {
        return IntegrationHealthCheck::query()
            ->forOperationsPage()
            ->get()
            ->map(fn (IntegrationHealthCheck $check): array => [
                'id' => $check->id,
                'label' => $check->label,
                'key' => $check->key,
                'status_label' => $check->status?->label() ?? (string) $check->status,
                'status_badge_class' => $this->statusBadgeClass($check),
                'summary' => (string) $check->summary,
                'response_time_label' => ($check->response_time_ms ?? 0).' ms',
                'checked_at_label' => $check->checked_at?->diffForHumans() ?? 'Never',
                'can_reset_circuit_breaker' => $check->hasTrippedCircuitBreaker(),
            ]);
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     summary: string,
     *     type_label: string,
     *     severity_label: string,
     *     severity_badge_class: string,
     *     organization_name: string,
     *     source_label: string,
     *     ip_address_label: string,
     *     occurred_at_label: string
     * }>
     */
    private function recentViolations(): Collection
    {
        return SecurityViolation::query()
            ->forIntegrationHealthFeed()
            ->limit(5)
            ->get()
            ->map(fn (SecurityViolation $violation): array => [
                'id' => $violation->id,
                'summary' => $violation->summary,
                'type_label' => $violation->type?->label() ?? (string) $violation->type,
                'severity_label' => $violation->severity?->label() ?? (string) $violation->severity,
                'severity_badge_class' => $this->severityBadgeClass($violation),
                'organization_name' => $violation->organization?->name ?? 'Platform',
                'source_label' => SecurityViolationTablePresenter::sourceLabel($violation),
                'ip_address_label' => $violation->ip_address ?? 'No IP captured',
                'occurred_at_label' => $violation->occurred_at?->diffForHumans() ?? 'Unknown',
            ]);
    }

    private function statusBadgeClass(IntegrationHealthCheck $check): string
    {
        return match ($check->status) {
            IntegrationHealthStatus::HEALTHY => 'bg-emerald-100 text-emerald-700',
            IntegrationHealthStatus::DEGRADED => 'bg-amber-100 text-amber-700',
            default => 'bg-rose-100 text-rose-700',
        };
    }

    private function severityBadgeClass(SecurityViolation $violation): string
    {
        return match ($violation->severity?->value) {
            'critical', 'high' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };
    }
}
