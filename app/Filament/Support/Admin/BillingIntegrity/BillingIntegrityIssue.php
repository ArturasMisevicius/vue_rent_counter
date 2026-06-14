<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingIntegrity;

final readonly class BillingIntegrityIssue
{
    /**
     * @param  list<int>  $entityIds
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $problemType,
        public string $entityType,
        public array $entityIds,
        public string $severity,
        public string $recommendedAction,
        public int $organizationId,
        public array $context = [],
    ) {}

    public function label(): string
    {
        return __('admin.billing_cleanup.problem_types.'.$this->problemType);
    }

    public function recommendationLabel(): string
    {
        return __('admin.billing_cleanup.recommended_actions.'.$this->recommendedAction);
    }

    public function severityLabel(): string
    {
        return __('admin.billing_cleanup.severities.'.$this->severity);
    }

    /**
     * @return array{
     *     problem_type: string,
     *     entity_type: string,
     *     entity_ids: list<int>,
     *     severity: string,
     *     recommended_action: string,
     *     organization_id: int,
     *     count: int,
     *     context: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'problem_type' => $this->problemType,
            'entity_type' => $this->entityType,
            'entity_ids' => $this->entityIds,
            'severity' => $this->severity,
            'recommended_action' => $this->recommendedAction,
            'organization_id' => $this->organizationId,
            'count' => count($this->entityIds),
            'context' => $this->context,
        ];
    }
}
