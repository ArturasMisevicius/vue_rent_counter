<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Configuration options for automated billing execution.
 * 
 * Immutable value object that encapsulates all billing
 * configuration options with validation and defaults.
 * 
 * @package App\ValueObjects
 */
final readonly class BillingOptions
{
    public function __construct(
        public readonly \Carbon\Carbon $startDate,
        public readonly \Carbon\Carbon $endDate,
        public readonly \App\Enums\BillingSchedule $schedule = \App\Enums\BillingSchedule::MONTHLY,
        public readonly bool $generateInvoices = true,
        public readonly bool $includeSharedServices = true,
        private readonly bool $autoCollectReadings = false,
        private readonly bool $processSharedServices = true,
        private readonly bool $regenerateExisting = false,
        private readonly bool $createZeroInvoices = false,
        private readonly bool $autoApprove = false,
        private readonly bool $requireApproval = false,
        private readonly float $approvalThreshold = 1000.0,
        private readonly ?array $tenantIds = null,
        private readonly ?array $customFilter = null,
        private readonly array $additionalOptions = [],
        private readonly array $excludedServices = [],
        private readonly array $customRates = [],
    ) {}

    public static function default(): self
    {
        return new self(
            startDate: \Carbon\Carbon::now()->startOfMonth(),
            endDate: \Carbon\Carbon::now()->endOfMonth(),
        );
    }

    /**
     * Create a new BillingOptions instance.
     */
    public static function create(
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null,
        \App\Enums\BillingSchedule $schedule = \App\Enums\BillingSchedule::MONTHLY,
        bool $generateInvoices = true,
        bool $includeSharedServices = true,
        bool $autoCollectReadings = false,
        bool $processSharedServices = false,
        bool $regenerateExisting = false,
        bool $createZeroInvoices = false,
        bool $autoApprove = false,
        bool $requireApproval = false,
        float $approvalThreshold = 1000.0,
        ?array $tenantIds = null,
        ?array $customFilter = null,
        array $additionalOptions = [],
        array $excludedServices = [],
        array $customRates = [],
    ): self {
        $startDate = $startDate ?? \Carbon\Carbon::now()->startOfMonth();
        $endDate = $endDate ?? \Carbon\Carbon::now()->endOfMonth();
        
        return new self(
            startDate: $startDate,
            endDate: $endDate,
            schedule: $schedule,
            generateInvoices: $generateInvoices,
            includeSharedServices: $includeSharedServices,
            autoCollectReadings: $autoCollectReadings,
            processSharedServices: $processSharedServices,
            regenerateExisting: $regenerateExisting,
            createZeroInvoices: $createZeroInvoices,
            autoApprove: $autoApprove,
            requireApproval: $requireApproval,
            approvalThreshold: $approvalThreshold,
            tenantIds: $tenantIds,
            customFilter: $customFilter,
            additionalOptions: $additionalOptions,
            excludedServices: $excludedServices,
            customRates: $customRates,
        );
    }

    public static function fromArray(array $options): self
    {
        return new self(
            startDate: isset($options['start_date']) ? \Carbon\Carbon::parse($options['start_date']) : \Carbon\Carbon::now()->startOfMonth(),
            endDate: isset($options['end_date']) ? \Carbon\Carbon::parse($options['end_date']) : \Carbon\Carbon::now()->endOfMonth(),
            schedule: $options['schedule'] ?? \App\Enums\BillingSchedule::MONTHLY,
            generateInvoices: $options['generate_invoices'] ?? true,
            includeSharedServices: $options['include_shared_services'] ?? true,
            autoCollectReadings: $options['auto_collect_readings'] ?? false,
            processSharedServices: $options['process_shared_services'] ?? false,
            regenerateExisting: $options['regenerate_existing'] ?? false,
            createZeroInvoices: $options['create_zero_invoices'] ?? false,
            autoApprove: $options['auto_approve'] ?? false,
            requireApproval: $options['require_approval'] ?? false,
            approvalThreshold: $options['approval_threshold'] ?? 1000.0,
            tenantIds: $options['tenant_ids'] ?? null,
            customFilter: $options['custom_filter'] ?? null,
            excludedServices: $options['excluded_services'] ?? [],
            customRates: $options['custom_rates'] ?? [],
            additionalOptions: array_diff_key($options, [
                'start_date' => true,
                'end_date' => true,
                'schedule' => true,
                'generate_invoices' => true,
                'include_shared_services' => true,
                'auto_collect_readings' => true,
                'process_shared_services' => true,
                'regenerate_existing' => true,
                'create_zero_invoices' => true,
                'auto_approve' => true,
                'require_approval' => true,
                'approval_threshold' => true,
                'tenant_ids' => true,
                'custom_filter' => true,
                'excluded_services' => true,
                'custom_rates' => true,
            ]),
        );
    }

    public function shouldAutoCollectReadings(): bool
    {
        return $this->autoCollectReadings;
    }

    public function shouldProcessSharedServices(): bool
    {
        return $this->processSharedServices;
    }

    public function shouldRegenerateExisting(): bool
    {
        return $this->regenerateExisting;
    }

    public function shouldOverwriteExisting(): bool
    {
        return $this->regenerateExisting;
    }

    public function shouldCreateZeroInvoices(): bool
    {
        return $this->createZeroInvoices;
    }

    public function shouldRequireApproval(): bool
    {
        return $this->requireApproval;
    }

    public function getApprovalThreshold(): float
    {
        return $this->approvalThreshold;
    }

    public function shouldAutoApprove(): bool
    {
        return $this->autoApprove;
    }

    public function getTenantIds(): ?array
    {
        return $this->tenantIds;
    }

    public function getCustomFilter(): ?array
    {
        return $this->customFilter;
    }

    public function getAdditionalOptions(): array
    {
        return $this->additionalOptions;
    }

    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->toISOString(),
            'end_date' => $this->endDate->toISOString(),
            'schedule' => $this->schedule->value,
            'generate_invoices' => $this->generateInvoices,
            'include_shared_services' => $this->includeSharedServices,
            'auto_collect_readings' => $this->autoCollectReadings,
            'process_shared_services' => $this->processSharedServices,
            'regenerate_existing' => $this->regenerateExisting,
            'create_zero_invoices' => $this->createZeroInvoices,
            'auto_approve' => $this->autoApprove,
            'require_approval' => $this->requireApproval,
            'approval_threshold' => $this->approvalThreshold,
            'tenant_ids' => $this->tenantIds,
            'custom_filter' => $this->customFilter,
            'excluded_services' => $this->excludedServices,
            'custom_rates' => $this->customRates,
            ...$this->additionalOptions,
        ];
    }

    public function withAutoCollectReadings(bool $autoCollect = true): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $autoCollect,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $this->autoApprove,
            requireApproval: $this->requireApproval,
            approvalThreshold: $this->approvalThreshold,
            tenantIds: $this->tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: $this->additionalOptions,
            excludedServices: $this->excludedServices,
            customRates: $this->customRates,
        );
    }

    public function withTenantIds(array $tenantIds): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $this->autoCollectReadings,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $this->autoApprove,
            requireApproval: $this->requireApproval,
            approvalThreshold: $this->approvalThreshold,
            tenantIds: $tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: $this->additionalOptions,
            excludedServices: $this->excludedServices,
            customRates: $this->customRates,
        );
    }

    public function withApprovalWorkflow(bool $requireApproval = true, float $threshold = 1000.0): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $this->autoCollectReadings,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $this->autoApprove,
            requireApproval: $requireApproval,
            approvalThreshold: $threshold,
            tenantIds: $this->tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: $this->additionalOptions,
            excludedServices: $this->excludedServices,
            customRates: $this->customRates,
        );
    }

    /**
     * Check if a service is excluded from billing.
     */
    public function isServiceExcluded(string $serviceType): bool
    {
        return in_array($serviceType, $this->excludedServices, true);
    }

    /**
     * Get custom rate for a service type.
     */
    public function getCustomRate(string $serviceType): ?float
    {
        return $this->customRates[$serviceType] ?? null;
    }

    /**
     * Get excluded services.
     */
    public function getExcludedServices(): array
    {
        return $this->excludedServices;
    }

    /**
     * Get custom rates.
     */
    public function getCustomRates(): array
    {
        return $this->customRates;
    }

    /**
     * Get automation level for billing operations.
     */
    public function getAutomationLevel(): \App\Enums\AutomationLevel
    {
        // Determine automation level based on configuration
        if ($this->autoApprove && !$this->requireApproval) {
            return \App\Enums\AutomationLevel::FULLY_AUTOMATED;
        }
        
        if ($this->requireApproval) {
            return \App\Enums\AutomationLevel::APPROVAL_REQUIRED;
        }
        
        if ($this->autoCollectReadings || $this->processSharedServices) {
            return \App\Enums\AutomationLevel::SEMI_AUTOMATED;
        }
        
        return \App\Enums\AutomationLevel::MANUAL;
    }

    /**
     * Get auto-approval threshold amount.
     */
    public function getAutoApprovalThreshold(): float
    {
        return $this->approvalThreshold;
    }

    /**
     * Check if approval workflow is required.
     */
    public function requiresApprovalWorkflow(): bool
    {
        return $this->requireApproval;
    }

    /**
     * Get additional option by key.
     */
    public function getAdditionalOption(string $key, mixed $default = null): mixed
    {
        return $this->additionalOptions[$key] ?? $default;
    }

    /**
     * Check if an additional option exists.
     */
    public function hasAdditionalOption(string $key): bool
    {
        return array_key_exists($key, $this->additionalOptions);
    }

    /**
     * Create a new instance with additional options.
     */
    public function withAdditionalOptions(array $options): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $this->autoCollectReadings,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $this->autoApprove,
            requireApproval: $this->requireApproval,
            approvalThreshold: $this->approvalThreshold,
            tenantIds: $this->tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: array_merge($this->additionalOptions, $options),
            excludedServices: $this->excludedServices,
            customRates: $this->customRates,
        );
    }

    /**
     * Create a new instance with excluded services.
     */
    public function withExcludedServices(array $excludedServices): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $this->autoCollectReadings,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $this->autoApprove,
            requireApproval: $this->requireApproval,
            approvalThreshold: $this->approvalThreshold,
            tenantIds: $this->tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: $this->additionalOptions,
            excludedServices: $excludedServices,
            customRates: $this->customRates,
        );
    }

    /**
     * Create a new instance with custom rates.
     */
    public function withCustomRates(array $customRates): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $this->autoCollectReadings,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $this->autoApprove,
            requireApproval: $this->requireApproval,
            approvalThreshold: $this->approvalThreshold,
            tenantIds: $this->tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: $this->additionalOptions,
            excludedServices: $this->excludedServices,
            customRates: $customRates,
        );
    }

    /**
     * Create a new instance with automation settings.
     */
    public function withAutomation(
        bool $autoCollectReadings = true,
        bool $autoApprove = false,
        bool $requireApproval = false
    ): self {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            schedule: $this->schedule,
            generateInvoices: $this->generateInvoices,
            includeSharedServices: $this->includeSharedServices,
            autoCollectReadings: $autoCollectReadings,
            processSharedServices: $this->processSharedServices,
            regenerateExisting: $this->regenerateExisting,
            createZeroInvoices: $this->createZeroInvoices,
            autoApprove: $autoApprove,
            requireApproval: $requireApproval,
            approvalThreshold: $this->approvalThreshold,
            tenantIds: $this->tenantIds,
            customFilter: $this->customFilter,
            additionalOptions: $this->additionalOptions,
            excludedServices: $this->excludedServices,
            customRates: $this->customRates,
        );
    }

    /**
     * Validate the billing options configuration.
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->startDate->isAfter($this->endDate)) {
            $errors[] = 'Start date must be before end date';
        }

        if ($this->approvalThreshold < 0) {
            $errors[] = 'Approval threshold must be non-negative';
        }

        if ($this->autoApprove && $this->requireApproval) {
            $errors[] = 'Cannot have both auto-approve and require approval enabled';
        }

        if ($this->tenantIds !== null && !is_array($this->tenantIds)) {
            $errors[] = 'Tenant IDs must be an array or null';
        }

        return $errors;
    }

    /**
     * Check if the configuration is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}