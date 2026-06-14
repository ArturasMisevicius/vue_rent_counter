<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenants;

use App\Enums\BillingReadinessStatus;

final readonly class TenantBillingReadinessResult
{
    /**
     * @param  array<int, string>  $blockingErrors
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $nextSteps
     * @param  array<int, array{label: string, status: string, message: string|null}>  $checks
     */
    public function __construct(
        public BillingReadinessStatus $status,
        public array $blockingErrors = [],
        public array $warnings = [],
        public array $nextSteps = [],
        public array $checks = [],
    ) {}

    public function isReady(): bool
    {
        return $this->status === BillingReadinessStatus::READY;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /**
     * @return array{
     *     status: string,
     *     label: string|null,
     *     blocking_errors: array<int, string>,
     *     warnings: array<int, string>,
     *     next_steps: array<int, string>,
     *     checks: array<int, array{label: string, status: string, message: string|null}>
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'label' => $this->status->getLabel(),
            'blocking_errors' => $this->blockingErrors,
            'warnings' => $this->warnings,
            'next_steps' => $this->nextSteps,
            'checks' => $this->checks,
        ];
    }
}
