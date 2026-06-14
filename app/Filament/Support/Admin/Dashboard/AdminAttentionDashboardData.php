<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Dashboard;

final readonly class AdminAttentionDashboardData
{
    /**
     * @param  array<string, mixed>  $organization
     * @param  array<string, mixed>  $currentBillingPeriod
     * @param  list<array<string, mixed>>  $topCards
     * @param  list<array<string, mixed>>  $billingCards
     * @param  list<array<string, mixed>>  $tenantOnboardingCards
     * @param  list<array<string, mixed>>  $configurationHealthCards
     * @param  list<array<string, mixed>>  $contractCards
     * @param  list<array<string, mixed>>  $documentCards
     * @param  list<array<string, mixed>>  $moveOutCards
     * @param  list<array<string, mixed>>  $dataIntegrityCards
     * @param  list<array<string, mixed>>  $needsActionItems
     * @param  list<array<string, mixed>>  $billingProgressSteps
     * @param  list<array<string, mixed>>  $recentActivity
     * @param  array<string, bool>  $widgetVisibility
     * @param  array<string, int>  $counts
     */
    public function __construct(
        public array $organization,
        public array $currentBillingPeriod,
        public int $billingCompletion,
        public array $topCards,
        public array $billingCards,
        public array $tenantOnboardingCards,
        public array $configurationHealthCards,
        public array $contractCards,
        public array $documentCards,
        public array $moveOutCards,
        public array $dataIntegrityCards,
        public array $needsActionItems,
        public array $billingProgressSteps,
        public array $recentActivity,
        public array $widgetVisibility,
        public array $counts,
        public string $emptyStateTitle,
        public string $emptyStateDescription,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => [
                'organization_name' => $this->organization['name'] ?? __('dashboard.not_available'),
                'billing_period' => $this->currentBillingPeriod['name'] ?? __('dashboard.attention.empty.no_period'),
                'billing_completion' => $this->billingCompletion,
                'has_urgent_actions' => $this->needsActionItems !== [],
                'empty_title' => $this->emptyStateTitle,
                'empty_description' => $this->emptyStateDescription,
            ],
            'organization' => $this->organization,
            'current_billing_period' => $this->currentBillingPeriod,
            'billing_completion' => $this->billingCompletion,
            'top_cards' => $this->topCards,
            'billing_cards' => $this->billingCards,
            'tenant_onboarding_cards' => $this->tenantOnboardingCards,
            'configuration_health_cards' => $this->configurationHealthCards,
            'contract_cards' => $this->contractCards,
            'document_cards' => $this->documentCards,
            'move_out_cards' => $this->moveOutCards,
            'data_integrity_cards' => $this->dataIntegrityCards,
            'needs_action_items' => $this->needsActionItems,
            'billing_progress' => [
                'period' => $this->currentBillingPeriod['name'] ?? __('dashboard.attention.empty.no_period'),
                'completion' => $this->billingCompletion,
                'total_invoices' => $this->counts['total_invoices'] ?? 0,
                'stages' => $this->billingProgressSteps,
            ],
            'billing_progress_steps' => $this->billingProgressSteps,
            'recent_activity' => $this->recentActivity,
            'visible_widgets' => $this->widgetVisibility,
            'widget_visibility' => $this->widgetVisibility,
            'counts' => $this->counts,
            'empty_state_title' => $this->emptyStateTitle,
            'empty_state_description' => $this->emptyStateDescription,
        ];
    }
}
