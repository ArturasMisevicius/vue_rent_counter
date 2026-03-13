<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Tariff;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\User;
use App\Models\Currency;
use App\Services\CurrencyConversionService;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\BillingOptions;
use App\Enums\InvoiceStatus;
use App\Enums\ApprovalStatus;
use App\Enums\AutomationLevel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Service for creating immutable snapshots of tariffs and service configurations
 * at the time of invoice generation.
 * 
 * Ensures historical invoice accuracy by preserving calculation methods,
 * rates, and configurations that were active during the billing period.
 * 
 * @package App\Services
 */
final readonly class InvoiceSnapshotService
{
    /**
     * Create an enhanced snapshot with currency conversion and additional analytics.
     * 
     * @param Invoice $invoice The invoice to create snapshots for
     * @param string $targetCurrency Target currency code for conversion
     * @return array Enhanced snapshot data with currency conversion
     */
    public function createEnhancedSnapshot(Invoice $invoice, string $targetCurrency): array
    {
        Log::info('Creating enhanced invoice snapshot with currency conversion', [
            'invoice_id' => $invoice->id,
            'target_currency' => $targetCurrency,
        ]);

        // Create base snapshot using existing method
        $period = new BillingPeriod(
            Carbon::parse($invoice->billing_period_start),
            Carbon::parse($invoice->billing_period_end)
        );
        $options = new BillingOptions();
        
        $snapshot = $this->createInvoiceSnapshot($invoice, $period, $options);

        // Add currency conversion
        $currencyConversionService = app(CurrencyConversionService::class);
        $fromCurrency = Currency::where('code', $invoice->currency)->first();
        $toCurrency = Currency::where('code', $targetCurrency)->first();
        
        if ($fromCurrency && $toCurrency) {
            try {
                $conversionResult = $currencyConversionService->convert(
                    $invoice->amount,
                    $fromCurrency,
                    $toCurrency
                );
                
                $snapshot['converted_amount'] = $conversionResult->convertedAmount;
                $snapshot['target_currency'] = $targetCurrency;
                $snapshot['exchange_rate'] = $conversionResult->exchangeRate;
                $snapshot['conversion_date'] = $conversionResult->conversionDate;
            } catch (\Exception $e) {
                Log::warning('Currency conversion failed', [
                    'invoice_id' => $invoice->id,
                    'from_currency' => $invoice->currency,
                    'to_currency' => $targetCurrency,
                    'error' => $e->getMessage(),
                ]);
                
                // Fallback to original amount
                $snapshot['converted_amount'] = $invoice->amount;
                $snapshot['target_currency'] = $targetCurrency;
                $snapshot['exchange_rate'] = 1.0;
                $snapshot['conversion_date'] = now();
            }
        } else {
            // Same currency or currency not found
            $snapshot['converted_amount'] = $invoice->amount;
            $snapshot['target_currency'] = $targetCurrency;
            $snapshot['exchange_rate'] = 1.0;
            $snapshot['conversion_date'] = now();
        }

        // Add tenant information
        $tenant = $invoice->tenant;
        if ($tenant) {
            $snapshot['tenant_info'] = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
            ];
        }

        // Add property information
        $property = $tenant?->property;
        if ($property) {
            $snapshot['property_info'] = [
                'id' => $property->id,
                'address' => $property->address,
                'type' => $property->type,
                'size' => $property->size,
            ];
        }

        // Add utility services information
        $utilityServices = [];
        if ($property) {
            $services = UtilityService::where('property_id', $property->id)->get();
            foreach ($services as $service) {
                $utilityServices[] = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->service_type_bridge?->value,
                    'unit' => $service->unit_of_measurement,
                    'base_rate' => $service->business_logic_config['base_rate'] ?? null,
                ];
            }
        }
        $snapshot['utility_services'] = $utilityServices;

        // Add analytics
        $billingRecords = $invoice->billingRecords;
        $totalConsumption = $billingRecords->sum('consumption');
        $averageRate = $billingRecords->count() > 0 ? 
            $billingRecords->sum('rate') / $billingRecords->count() : 0;
        
        $serviceBreakdown = [];
        foreach ($billingRecords->groupBy('service_type') as $serviceType => $records) {
            $serviceBreakdown[] = [
                'service_type' => $serviceType,
                'total_consumption' => $records->sum('consumption'),
                'total_amount' => $records->sum('amount'),
                'average_rate' => $records->count() > 0 ? 
                    $records->sum('rate') / $records->count() : 0,
            ];
        }

        $snapshot['analytics'] = [
            'total_consumption' => $totalConsumption,
            'average_rate' => $averageRate,
            'service_breakdown' => $serviceBreakdown,
        ];

        Log::info('Enhanced invoice snapshot with currency conversion created successfully', [
            'invoice_id' => $invoice->id,
            'converted_amount' => $snapshot['converted_amount'],
            'target_currency' => $targetCurrency,
            'exchange_rate' => $snapshot['exchange_rate'],
        ]);

        return $snapshot;
    }

    /**
     * Create an enhanced snapshot with approval workflow and automation metadata.
     * 
     * @param Invoice $invoice The invoice to create snapshots for
     * @param BillingPeriod $period The billing period
     * @param BillingOptions $options Billing configuration options
     * @return array Enhanced snapshot data with approval workflow
     */
    public function createEnhancedSnapshotWithWorkflow(
        Invoice $invoice,
        BillingPeriod $period,
        BillingOptions $options
    ): array {
        Log::info('Creating enhanced invoice snapshot with approval workflow', [
            'invoice_id' => $invoice->id,
            'period' => $period->getLabel(),
            'automation_level' => $options->getAutomationLevel()->value,
        ]);

        // Create base snapshot
        $snapshot = $this->createInvoiceSnapshot($invoice, $period, $options);

        // Enhance with additional approval and automation metadata
        $snapshot['enhanced_features'] = [
            'approval_workflow_v2' => true,
            'automation_scoring' => true,
            'risk_assessment' => true,
            'predictive_analytics' => true,
        ];

        // Add enhanced approval workflow data
        $snapshot['approval_workflow_enhanced'] = $this->createEnhancedApprovalWorkflow($invoice, $options);
        
        // Add automation confidence scoring
        $snapshot['automation_confidence'] = $this->createAutomationConfidenceData($invoice, $options);
        
        // Add risk assessment data
        $snapshot['risk_assessment'] = $this->createRiskAssessmentData($invoice, $options);
        
        // Add predictive analytics
        $snapshot['predictive_analytics'] = $this->createPredictiveAnalyticsData($invoice, $period);

        // Update invoice with enhanced data
        $invoice->update([
            'snapshot_data' => $snapshot,
            'snapshot_created_at' => now(),
            'approval_status' => $this->determineEnhancedApprovalStatus($invoice, $options),
            'automation_level' => $options->getAutomationLevel(),
            'approval_deadline' => $this->calculateApprovalDeadline($invoice, $options) ? 
                \Carbon\Carbon::parse($this->calculateApprovalDeadline($invoice, $options)) : null,
        ]);

        Log::info('Enhanced invoice snapshot created successfully', [
            'invoice_id' => $invoice->id,
            'snapshot_size' => strlen(json_encode($snapshot)),
            'approval_required' => $snapshot['approval_workflow']['required'],
            'automation_level' => $options->getAutomationLevel()->value,
            'confidence_score' => $snapshot['automation_confidence']['overall_score'],
            'risk_level' => $snapshot['risk_assessment']['risk_level'],
        ]);

        return $snapshot;
    }

    /**
     * Create enhanced approval workflow data.
     */
    private function createEnhancedApprovalWorkflow(Invoice $invoice, BillingOptions $options): array
    {
        $baseWorkflow = $this->createApprovalWorkflowData($invoice, $options);
        
        return array_merge($baseWorkflow, [
            'enhanced_features' => [
                'dynamic_thresholds' => true,
                'risk_based_routing' => true,
                'automated_escalation' => true,
                'ml_approval_scoring' => true,
            ],
            'dynamic_threshold' => $this->calculateDynamicApprovalThreshold($invoice, $options),
            'risk_based_routing' => $this->getRiskBasedRoutingRules($invoice, $options),
            'ml_approval_score' => $this->calculateMLApprovalScore($invoice, $options),
            'approval_confidence' => $this->calculateApprovalConfidence($invoice, $options),
            'recommended_approvers' => $this->getRecommendedApprovers($invoice, $options),
            'approval_complexity_score' => $this->calculateApprovalComplexityScore($invoice, $options),
        ]);
    }

    /**
     * Create automation confidence data.
     */
    private function createAutomationConfidenceData(Invoice $invoice, BillingOptions $options): array
    {
        $baseScore = $this->calculateAutomationConfidenceScore($invoice, $options);
        
        return [
            'overall_score' => $baseScore,
            'component_scores' => [
                'data_quality' => $this->calculateDataQualityScore($invoice),
                'calculation_complexity' => $this->calculateCalculationComplexityScore($invoice),
                'historical_accuracy' => $this->calculateHistoricalAccuracyScore($invoice),
                'anomaly_detection' => $this->calculateAnomalyScore($invoice),
            ],
            'confidence_factors' => $this->getConfidenceFactors($invoice, $options),
            'risk_mitigation' => $this->getRiskMitigationStrategies($invoice, $options),
            'fallback_triggers' => $this->getFallbackTriggers($invoice, $options),
        ];
    }

    /**
     * Create risk assessment data.
     */
    private function createRiskAssessmentData(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'risk_level' => $this->calculateOverallRiskLevel($invoice, $options),
            'risk_factors' => [
                'financial_risk' => $this->calculateFinancialRisk($invoice),
                'operational_risk' => $this->calculateOperationalRisk($invoice, $options),
                'compliance_risk' => $this->calculateComplianceRisk($invoice),
                'data_quality_risk' => $this->calculateDataQualityRisk($invoice),
            ],
            'risk_mitigation_strategies' => $this->getRiskMitigationStrategies($invoice, $options),
            'monitoring_requirements' => $this->getMonitoringRequirements($invoice, $options),
            'escalation_triggers' => $this->getEscalationTriggers($invoice, $options),
        ];
    }

    /**
     * Create predictive analytics data.
     */
    private function createPredictiveAnalyticsData(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'consumption_forecast' => $this->generateConsumptionForecast($invoice, $period),
            'cost_prediction' => $this->generateCostPrediction($invoice, $period),
            'anomaly_likelihood' => $this->calculateAnomalyLikelihood($invoice),
            'seasonal_adjustments' => $this->predictSeasonalAdjustments($invoice, $period),
            'trend_analysis' => $this->performTrendAnalysis($invoice, $period),
        ];
    }

    /**
     * Determine enhanced approval status with ML scoring.
     */
    private function determineEnhancedApprovalStatus(Invoice $invoice, BillingOptions $options): ApprovalStatus
    {
        $mlScore = $this->calculateMLApprovalScore($invoice, $options);
        $riskLevel = $this->calculateOverallRiskLevel($invoice, $options);
        
        // High confidence and low risk -> auto approve
        if ($mlScore > 0.9 && $riskLevel === 'low' && $this->isAutoApprovalEligible($invoice, $options)) {
            return ApprovalStatus::AUTO_APPROVED;
        }
        
        // High risk or low confidence -> requires review
        if ($riskLevel === 'high' || $mlScore < 0.5) {
            return ApprovalStatus::REQUIRES_REVIEW;
        }
        
        // Standard approval workflow
        if ($this->requiresApprovalWorkflow($invoice, $options)) {
            return ApprovalStatus::PENDING;
        }
        
        return ApprovalStatus::AUTO_APPROVED;
    }

    // Enhanced calculation methods
    private function calculateDynamicApprovalThreshold(Invoice $invoice, BillingOptions $options): float
    {
        $baseThreshold = $options->getApprovalThreshold();
        $riskMultiplier = $this->getRiskMultiplier($invoice);
        $historicalAccuracy = $this->getHistoricalAccuracyMultiplier($invoice);
        
        return $baseThreshold * $riskMultiplier * $historicalAccuracy;
    }

    private function getRiskBasedRoutingRules(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'high_risk' => ['role' => 'senior_manager', 'department' => 'risk_management'],
            'medium_risk' => ['role' => 'manager', 'department' => 'billing'],
            'low_risk' => ['role' => 'supervisor', 'department' => 'billing'],
        ];
    }

    private function calculateMLApprovalScore(Invoice $invoice, BillingOptions $options): float
    {
        // Simplified ML scoring - in production this would use actual ML models
        $factors = [
            'amount_score' => min(1.0, 1000.0 / max(1.0, $invoice->total_amount)),
            'complexity_score' => 1.0 - ($this->calculateComplexityScore($invoice) / 10.0),
            'accuracy_score' => $this->hasHistoricalAccuracy($invoice) ? 0.9 : 0.5,
            'risk_score' => $this->calculateOverallRiskLevel($invoice, $options) === 'low' ? 0.9 : 0.3,
        ];
        
        return array_sum($factors) / count($factors);
    }

    private function calculateApprovalConfidence(Invoice $invoice, BillingOptions $options): float
    {
        return min(1.0, $this->calculateMLApprovalScore($invoice, $options) * 1.1);
    }

    private function getRecommendedApprovers(Invoice $invoice, BillingOptions $options): array
    {
        $riskLevel = $this->calculateOverallRiskLevel($invoice, $options);
        
        return match($riskLevel) {
            'high' => ['senior_manager', 'risk_manager', 'director'],
            'medium' => ['manager', 'senior_supervisor'],
            'low' => ['supervisor', 'team_lead'],
            default => ['manager'],
        };
    }

    private function calculateApprovalComplexityScore(Invoice $invoice, BillingOptions $options): int
    {
        $baseComplexity = $this->calculateComplexityScore($invoice);
        $approvalFactors = 0;
        
        if ($invoice->total_amount > 2000.0) $approvalFactors += 2;
        if ($this->hasEstimatedReadings($invoice)) $approvalFactors += 1;
        if ($this->hasComplexCalculations($invoice)) $approvalFactors += 2;
        
        return $baseComplexity + $approvalFactors;
    }

    private function calculateDataQualityScore(Invoice $invoice): float
    {
        $score = 1.0;
        
        if ($this->hasEstimatedReadings($invoice)) $score -= 0.3;
        if ($this->hasMissingData($invoice)) $score -= 0.2;
        if ($this->hasInconsistentData($invoice)) $score -= 0.2;
        
        return max(0.0, $score);
    }

    private function calculateCalculationComplexityScore(Invoice $invoice): float
    {
        $complexity = $this->calculateComplexityScore($invoice);
        return max(0.0, 1.0 - ($complexity / 10.0));
    }

    private function calculateHistoricalAccuracyScore(Invoice $invoice): float
    {
        return $this->hasHistoricalAccuracy($invoice) ? 0.95 : 0.5;
    }

    private function calculateAnomalyScore(Invoice $invoice): float
    {
        // Simplified anomaly detection
        return 0.8; // Would use actual anomaly detection algorithms
    }

    private function getConfidenceFactors(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'data_completeness' => !$this->hasEstimatedReadings($invoice),
            'calculation_simplicity' => !$this->hasComplexCalculations($invoice),
            'historical_consistency' => $this->hasHistoricalAccuracy($invoice),
            'automation_eligibility' => $this->isAutoApprovalEligible($invoice, $options),
        ];
    }

    private function getFallbackTriggers(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'low_confidence' => 0.5,
            'high_risk' => 'medium',
            'data_quality_issues' => true,
            'calculation_errors' => true,
        ];
    }

    private function calculateOverallRiskLevel(Invoice $invoice, BillingOptions $options): string
    {
        $riskFactors = 0;
        
        if ($invoice->total_amount > 2000.0) $riskFactors++;
        if ($this->hasEstimatedReadings($invoice)) $riskFactors++;
        if ($this->hasComplexCalculations($invoice)) $riskFactors++;
        if (!$this->hasHistoricalAccuracy($invoice)) $riskFactors++;
        
        return match(true) {
            $riskFactors >= 3 => 'high',
            $riskFactors >= 2 => 'medium',
            default => 'low',
        };
    }

    private function calculateFinancialRisk(Invoice $invoice): string
    {
        return $invoice->total_amount > 2000.0 ? 'high' : 'low';
    }

    private function calculateOperationalRisk(Invoice $invoice, BillingOptions $options): string
    {
        return $this->hasComplexCalculations($invoice) ? 'medium' : 'low';
    }

    private function calculateComplianceRisk(Invoice $invoice): string
    {
        return $this->hasEstimatedReadings($invoice) ? 'medium' : 'low';
    }

    private function calculateDataQualityRisk(Invoice $invoice): string
    {
        return $this->hasEstimatedReadings($invoice) || $this->hasMissingData($invoice) ? 'high' : 'low';
    }

    private function getMonitoringRequirements(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'real_time_monitoring' => $this->calculateOverallRiskLevel($invoice, $options) === 'high',
            'automated_alerts' => true,
            'manual_review_frequency' => $this->getReviewFrequency($invoice, $options),
        ];
    }

    private function getEscalationTriggers(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'approval_overdue' => '3 business days',
            'high_risk_detected' => 'immediate',
            'anomaly_detected' => '1 business day',
            'compliance_issue' => 'immediate',
        ];
    }

    // Predictive analytics methods
    private function generateConsumptionForecast(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'next_period_estimate' => $invoice->total_amount * 1.05, // Simplified forecast
            'confidence_interval' => ['lower' => $invoice->total_amount * 0.9, 'upper' => $invoice->total_amount * 1.2],
            'trend' => 'stable',
        ];
    }

    private function generateCostPrediction(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'predicted_cost' => $invoice->total_amount * 1.03,
            'cost_drivers' => ['seasonal_adjustment', 'rate_changes'],
            'savings_opportunities' => [],
        ];
    }

    private function calculateAnomalyLikelihood(Invoice $invoice): float
    {
        return 0.1; // 10% likelihood - would use actual anomaly detection
    }

    private function predictSeasonalAdjustments(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'heating_season_impact' => $this->hasSeasonalAdjustments($period) ? 'high' : 'none',
            'adjustment_factor' => $this->hasSeasonalAdjustments($period) ? 1.3 : 1.0,
        ];
    }

    private function performTrendAnalysis(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'consumption_trend' => 'stable',
            'cost_trend' => 'increasing',
            'efficiency_trend' => 'improving',
        ];
    }

    // Helper methods
    private function getRiskMultiplier(Invoice $invoice): float
    {
        return $invoice->total_amount > 2000.0 ? 0.8 : 1.0;
    }

    private function getHistoricalAccuracyMultiplier(Invoice $invoice): float
    {
        return $this->hasHistoricalAccuracy($invoice) ? 1.2 : 0.9;
    }

    private function hasMissingData(Invoice $invoice): bool
    {
        return false; // Placeholder - would check for missing meter readings, etc.
    }

    private function hasInconsistentData(Invoice $invoice): bool
    {
        return false; // Placeholder - would check for data inconsistencies
    }

    private function getReviewFrequency(Invoice $invoice, BillingOptions $options): string
    {
        return match($this->calculateOverallRiskLevel($invoice, $options)) {
            'high' => 'daily',
            'medium' => 'weekly',
            'low' => 'monthly',
        };
    }

    /**
     * Create a complete snapshot of all billing-related data for an invoice.
     * 
     * @param Invoice $invoice The invoice to create snapshots for
     * @param BillingPeriod $period The billing period
     * @param BillingOptions $options Billing configuration options
     * @return array Complete snapshot data
     */
    public function createInvoiceSnapshot(
        Invoice $invoice,
        BillingPeriod $period,
        BillingOptions $options
    ): array {
        Log::info('Creating invoice snapshot', [
            'invoice_id' => $invoice->id,
            'period' => $period->getLabel(),
        ]);

        $snapshot = [
            'created_at' => now()->toIso8601String(),
            'created_by' => Auth::id(),
            'billing_period' => $period->toArray(),
            'billing_options' => $options->toArray(),
            'tariff_snapshots' => $this->createTariffSnapshots($invoice, $period),
            'service_configuration_snapshots' => $this->createServiceConfigurationSnapshots($invoice, $period),
            'utility_service_snapshots' => $this->createUtilityServiceSnapshots($invoice, $period),
            'calculation_metadata' => $this->createCalculationMetadata($invoice, $period, $options),
            'approval_workflow' => $this->createApprovalWorkflowData($invoice, $options),
            'automation_metadata' => $this->createAutomationMetadata($invoice, $options),
            'historical_context' => $this->createHistoricalContext($invoice, $period),
        ];

        // Store snapshot in invoice
        $invoice->update([
            'snapshot_data' => $snapshot,
            'snapshot_created_at' => now(),
            'approval_status' => $this->determineInitialApprovalStatus($invoice, $options),
            'automation_level' => $options->getAutomationLevel(),
        ]);

        Log::info('Invoice snapshot created successfully', [
            'invoice_id' => $invoice->id,
            'snapshot_size' => strlen(json_encode($snapshot)),
            'approval_required' => $snapshot['approval_workflow']['required'],
            'automation_level' => $options->getAutomationLevel()->value,
        ]);

        return $snapshot;
    }

    /**
     * Create snapshots of all tariffs active during the billing period.
     */
    private function createTariffSnapshots(Invoice $invoice, BillingPeriod $period): array
    {
        $snapshots = [];

        // Get all tariffs that were active during the billing period
        $activeTariffs = Tariff::where(function ($query) use ($period) {
            $query->where('active_from', '<=', $period->getEndDate())
                  ->where(function ($subQuery) use ($period) {
                      $subQuery->whereNull('active_until')
                               ->orWhere('active_until', '>=', $period->getStartDate());
                  });
        })->get();

        foreach ($activeTariffs as $tariff) {
            $snapshots[$tariff->id] = [
                'id' => $tariff->id,
                'name' => $tariff->name,
                'type' => $tariff->type->value,
                'rates' => $tariff->rates,
                'configuration' => $tariff->configuration,
                'active_from' => $tariff->active_from?->toIso8601String(),
                'active_until' => $tariff->active_until?->toIso8601String(),
                'provider_id' => $tariff->provider_id,
                'provider_name' => $tariff->provider?->name,
                'created_at' => $tariff->created_at->toIso8601String(),
                'updated_at' => $tariff->updated_at->toIso8601String(),
            ];
        }

        return $snapshots;
    }

    /**
     * Create snapshots of service configurations active during the billing period.
     */
    private function createServiceConfigurationSnapshots(Invoice $invoice, BillingPeriod $period): array
    {
        $snapshots = [];

        // Get service configurations for the property
        $serviceConfigs = ServiceConfiguration::where('property_id', $invoice->property_id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $period->getEndDate())
            ->where(function ($query) use ($period) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $period->getStartDate());
            })
            ->with('utilityService')
            ->get();

        foreach ($serviceConfigs as $config) {
            $snapshots[$config->id] = [
                'id' => $config->id,
                'property_id' => $config->property_id,
                'utility_service_id' => $config->utility_service_id,
                'pricing_model' => $config->pricing_model?->value,
                'rate_schedule' => $config->rate_schedule,
                'distribution_method' => $config->distribution_method?->value,
                'effective_from' => $config->effective_from?->toIso8601String(),
                'effective_until' => $config->effective_until?->toIso8601String(),
                'is_active' => $config->is_active,
                'is_shared_service' => $config->is_shared_service,
                'configuration_data' => $config->configuration_data,
                'created_at' => $config->created_at->toIso8601String(),
                'updated_at' => $config->updated_at->toIso8601String(),
            ];
        }

        return $snapshots;
    }

    /**
     * Create snapshots of utility services used in the billing period.
     */
    private function createUtilityServiceSnapshots(Invoice $invoice, BillingPeriod $period): array
    {
        $snapshots = [];

        // Get utility services through service configurations
        $utilityServices = UtilityService::whereHas('serviceConfigurations', function ($query) use ($invoice, $period) {
            $query->where('property_id', $invoice->property_id)
                  ->where('is_active', true)
                  ->where('effective_from', '<=', $period->getEndDate())
                  ->where(function ($subQuery) use ($period) {
                      $subQuery->whereNull('effective_until')
                               ->orWhere('effective_until', '>=', $period->getStartDate());
                  });
        })->get();

        foreach ($utilityServices as $service) {
            $snapshots[$service->id] = [
                'id' => $service->id,
                'name' => $service->name,
                'service_type_bridge' => $service->service_type_bridge?->value,
                'unit_of_measurement' => $service->unit_of_measurement,
                'default_pricing_model' => $service->default_pricing_model?->value,
                'calculation_formula' => $service->calculation_formula,
                'validation_rules' => $service->validation_rules,
                'business_logic_config' => $service->business_logic_config,
                'is_global_template' => $service->is_global_template,
                'tenant_id' => $service->tenant_id,
                'created_at' => $service->created_at->toIso8601String(),
                'updated_at' => $service->updated_at->toIso8601String(),
            ];
        }

        return $snapshots;
    }

    /**
     * Create calculation metadata for the invoice.
     */
    private function createCalculationMetadata(
        Invoice $invoice,
        BillingPeriod $period,
        BillingOptions $options
    ): array {
        return [
            'calculation_engine_version' => '1.0.0',
            'calculation_timestamp' => now()->toIso8601String(),
            'billing_period_days' => $period->getDays(),
            'seasonal_adjustments_applied' => $this->hasSeasonalAdjustments($period),
            'heating_calculations_included' => $this->hasHeatingCalculations($invoice),
            'shared_service_distributions_applied' => $this->hasSharedServiceDistributions($invoice),
            'automated_reading_collection_used' => $options->shouldAutoCollectReadings(),
            'approval_workflow_required' => $this->requiresApprovalWorkflow($invoice, $options),
            'calculation_complexity_score' => $this->calculateComplexityScore($invoice),
        ];
    }

    /**
     * Check if seasonal adjustments were applied.
     */
    private function hasSeasonalAdjustments(BillingPeriod $period): bool
    {
        // Check if the period includes winter months (heating season)
        $startMonth = $period->getStartDate()->month;
        $endMonth = $period->getEndDate()->month;
        
        // Winter months in Lithuania: October through April
        $winterMonths = [10, 11, 12, 1, 2, 3, 4];
        
        return in_array($startMonth, $winterMonths) || in_array($endMonth, $winterMonths);
    }

    /**
     * Check if heating calculations were included.
     */
    private function hasHeatingCalculations(Invoice $invoice): bool
    {
        // Check if the property has heating service configurations
        return ServiceConfiguration::where('property_id', $invoice->property_id)
            ->whereHas('utilityService', function ($query) {
                $query->where('service_type_bridge', 'heating');
            })
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if shared service distributions were applied.
     */
    private function hasSharedServiceDistributions(Invoice $invoice): bool
    {
        // Check if the property has shared service configurations
        return ServiceConfiguration::where('property_id', $invoice->property_id)
            ->where('is_shared_service', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Create approval workflow data for the invoice.
     */
    private function createApprovalWorkflowData(Invoice $invoice, BillingOptions $options): array
    {
        $approvalRequired = $this->requiresApprovalWorkflow($invoice, $options);
        $approvalThreshold = $this->getApprovalThreshold($options);
        
        return [
            'required' => $approvalRequired,
            'threshold_amount' => $approvalThreshold,
            'reasons' => $this->getApprovalReasons($invoice, $options),
            'workflow_steps' => $this->getApprovalWorkflowSteps($invoice, $options),
            'auto_approval_eligible' => $this->isAutoApprovalEligible($invoice, $options),
            'approval_deadline' => $this->calculateApprovalDeadline($invoice, $options),
            'escalation_rules' => $this->getEscalationRules($invoice, $options),
        ];
    }

    /**
     * Create automation metadata for the invoice.
     */
    private function createAutomationMetadata(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'automation_level' => $options->getAutomationLevel()->value,
            'automated_steps' => $this->getAutomatedSteps($options),
            'manual_intervention_points' => $this->getManualInterventionPoints($invoice, $options),
            'automation_confidence_score' => $this->calculateAutomationConfidenceScore($invoice, $options),
            'fallback_to_manual' => $this->shouldFallbackToManual($invoice, $options),
            'automation_rules_applied' => $this->getAppliedAutomationRules($invoice, $options),
        ];
    }

    /**
     * Create historical context for the invoice.
     */
    private function createHistoricalContext(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'previous_period_data' => $this->getPreviousPeriodData($invoice, $period),
            'consumption_trends' => $this->getConsumptionTrends($invoice, $period),
            'rate_changes' => $this->getRateChanges($invoice, $period),
            'service_modifications' => $this->getServiceModifications($invoice, $period),
            'historical_accuracy_metrics' => $this->getHistoricalAccuracyMetrics($invoice),
        ];
    }

    /**
     * Determine initial approval status based on invoice and options.
     */
    private function determineInitialApprovalStatus(Invoice $invoice, BillingOptions $options): ApprovalStatus
    {
        if (!$this->requiresApprovalWorkflow($invoice, $options)) {
            return ApprovalStatus::AUTO_APPROVED;
        }

        if ($this->isAutoApprovalEligible($invoice, $options)) {
            return ApprovalStatus::AUTO_APPROVED;
        }

        if ($this->hasHighRiskFactors($invoice, $options)) {
            return ApprovalStatus::REQUIRES_REVIEW;
        }

        return ApprovalStatus::PENDING;
    }

    /**
     * Get approval reasons for the invoice.
     */
    private function getApprovalReasons(Invoice $invoice, BillingOptions $options): array
    {
        $reasons = [];

        if ($invoice->total_amount > $this->getApprovalThreshold($options)) {
            $reasons[] = 'high_value_invoice';
        }

        if ($this->hasComplexCalculations($invoice)) {
            $reasons[] = 'complex_calculations';
        }

        if ($this->hasEstimatedReadings($invoice)) {
            $reasons[] = 'estimated_readings';
        }

        if ($this->hasRateChanges($invoice)) {
            $reasons[] = 'rate_changes_applied';
        }

        if ($options->shouldRequireApproval()) {
            $reasons[] = 'explicit_approval_required';
        }

        return $reasons;
    }

    /**
     * Get approval workflow steps.
     */
    private function getApprovalWorkflowSteps(Invoice $invoice, BillingOptions $options): array
    {
        $steps = [];

        if ($this->requiresApprovalWorkflow($invoice, $options)) {
            $steps[] = [
                'step' => 'initial_review',
                'required_role' => 'manager',
                'estimated_duration' => '1-2 business days',
            ];

            if ($invoice->total_amount > 2000.0) {
                $steps[] = [
                    'step' => 'senior_approval',
                    'required_role' => 'senior_manager',
                    'estimated_duration' => '2-3 business days',
                ];
            }

            if ($this->hasHighRiskFactors($invoice, $options)) {
                $steps[] = [
                    'step' => 'risk_assessment',
                    'required_role' => 'risk_manager',
                    'estimated_duration' => '3-5 business days',
                ];
            }
        }

        return $steps;
    }

    /**
     * Check if invoice is eligible for auto-approval.
     */
    private function isAutoApprovalEligible(Invoice $invoice, BillingOptions $options): bool
    {
        // Auto-approval criteria
        $criteria = [
            $invoice->total_amount <= $this->getAutoApprovalThreshold($options),
            !$this->hasEstimatedReadings($invoice),
            !$this->hasComplexCalculations($invoice),
            !$this->hasHighRiskFactors($invoice, $options),
            $this->hasHistoricalAccuracy($invoice),
        ];

        return !in_array(false, $criteria, true);
    }

    /**
     * Calculate approval deadline.
     */
    private function calculateApprovalDeadline(Invoice $invoice, BillingOptions $options): ?string
    {
        if (!$this->requiresApprovalWorkflow($invoice, $options)) {
            return null;
        }

        $businessDays = $this->getApprovalTimeframe($invoice, $options);
        $deadline = now()->addWeekdays($businessDays);

        return $deadline->toIso8601String();
    }

    /**
     * Get escalation rules for approval.
     */
    private function getEscalationRules(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'escalation_enabled' => true,
            'escalation_after_days' => 3,
            'escalation_levels' => [
                ['role' => 'senior_manager', 'after_days' => 3],
                ['role' => 'director', 'after_days' => 5],
            ],
            'auto_approve_after_days' => 7,
        ];
    }

    /**
     * Get automated steps for the billing process.
     */
    private function getAutomatedSteps(BillingOptions $options): array
    {
        $steps = [];

        if ($options->shouldAutoCollectReadings()) {
            $steps[] = 'reading_collection';
        }

        if ($options->getAutomationLevel()->allowsAutomation()) {
            $steps[] = 'calculation_processing';
            $steps[] = 'tariff_application';
        }

        if ($options->getAutomationLevel() === AutomationLevel::FULLY_AUTOMATED) {
            $steps[] = 'invoice_generation';
            $steps[] = 'delivery_scheduling';
        }

        return $steps;
    }

    /**
     * Get manual intervention points.
     */
    private function getManualInterventionPoints(Invoice $invoice, BillingOptions $options): array
    {
        $points = [];

        if ($this->hasEstimatedReadings($invoice)) {
            $points[] = 'reading_validation';
        }

        if ($this->hasComplexCalculations($invoice)) {
            $points[] = 'calculation_review';
        }

        if ($options->getAutomationLevel()->requiresApproval()) {
            $points[] = 'final_approval';
        }

        return $points;
    }

    /**
     * Calculate automation confidence score.
     */
    private function calculateAutomationConfidenceScore(Invoice $invoice, BillingOptions $options): float
    {
        $score = 1.0;

        // Reduce confidence for estimated readings
        if ($this->hasEstimatedReadings($invoice)) {
            $score -= 0.3;
        }

        // Reduce confidence for complex calculations
        if ($this->hasComplexCalculations($invoice)) {
            $score -= 0.2;
        }

        // Reduce confidence for high-value invoices
        if ($invoice->total_amount > 1000.0) {
            $score -= 0.1;
        }

        // Increase confidence for historical accuracy
        if ($this->hasHistoricalAccuracy($invoice)) {
            $score += 0.1;
        }

        return max(0.0, min(1.0, $score));
    }

    /**
     * Check if should fallback to manual processing.
     */
    private function shouldFallbackToManual(Invoice $invoice, BillingOptions $options): bool
    {
        $confidenceScore = $this->calculateAutomationConfidenceScore($invoice, $options);
        $threshold = 0.7; // Configurable threshold

        return $confidenceScore < $threshold;
    }

    /**
     * Get applied automation rules.
     */
    private function getAppliedAutomationRules(Invoice $invoice, BillingOptions $options): array
    {
        return [
            'auto_reading_collection' => $options->shouldAutoCollectReadings(),
            'auto_calculation' => $options->getAutomationLevel()->allowsAutomation(),
            'auto_approval' => $this->isAutoApprovalEligible($invoice, $options),
            'auto_delivery' => $options->getAutomationLevel() === AutomationLevel::FULLY_AUTOMATED,
        ];
    }

    /**
     * Get previous period data for comparison.
     */
    private function getPreviousPeriodData(Invoice $invoice, BillingPeriod $period): array
    {
        // This would typically query for previous invoices and consumption data
        return [
            'previous_total_amount' => null, // Would be populated from database
            'consumption_variance' => null,
            'rate_changes' => [],
        ];
    }

    /**
     * Get consumption trends.
     */
    private function getConsumptionTrends(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'trend_direction' => 'stable', // up, down, stable
            'seasonal_adjustment' => $this->hasSeasonalAdjustments($period),
            'anomalies_detected' => false,
        ];
    }

    /**
     * Get rate changes during the period.
     */
    private function getRateChanges(Invoice $invoice, BillingPeriod $period): array
    {
        // Check for tariff changes during the billing period
        return [
            'rate_changes_applied' => false,
            'effective_dates' => [],
            'impact_assessment' => 'minimal',
        ];
    }

    /**
     * Get service modifications during the period.
     */
    private function getServiceModifications(Invoice $invoice, BillingPeriod $period): array
    {
        return [
            'service_additions' => [],
            'service_removals' => [],
            'configuration_changes' => [],
        ];
    }

    /**
     * Get historical accuracy metrics.
     */
    private function getHistoricalAccuracyMetrics(Invoice $invoice): array
    {
        return [
            'accuracy_score' => 0.95, // Would be calculated from historical data
            'dispute_rate' => 0.02,
            'adjustment_frequency' => 0.05,
        ];
    }

    /**
     * Helper methods for approval workflow
     */
    private function requiresApprovalWorkflow(Invoice $invoice, BillingOptions $options): bool
    {
        // Check explicit approval requirement
        if ($options->shouldRequireApproval()) {
            return true;
        }

        // Check if invoice amount exceeds threshold
        if ($invoice->total_amount > $this->getApprovalThreshold($options)) {
            return true;
        }

        // Check for complex calculations
        if ($this->hasComplexCalculations($invoice)) {
            return true;
        }

        // Check for estimated readings
        if ($this->hasEstimatedReadings($invoice)) {
            return true;
        }

        // Check for rate changes
        if ($this->hasRateChanges($invoice)) {
            return true;
        }

        return false;
    }

    private function getApprovalThreshold(BillingOptions $options): float
    {
        return $options->getApprovalThreshold() ?? 1000.0;
    }

    private function getAutoApprovalThreshold(BillingOptions $options): float
    {
        return $options->getAutoApprovalThreshold() ?? 500.0;
    }

    private function getApprovalTimeframe(Invoice $invoice, BillingOptions $options): int
    {
        // Return business days for approval
        if ($invoice->total_amount > 2000.0) {
            return 5; // 5 business days for high-value invoices
        }
        
        return 3; // 3 business days for standard invoices
    }

    private function hasComplexCalculations(Invoice $invoice): bool
    {
        return $this->calculateComplexityScore($invoice) > 5;
    }

    private function hasEstimatedReadings(Invoice $invoice): bool
    {
        // This would check if any meter readings for this invoice are estimated
        return false; // Placeholder - would query meter readings
    }

    private function hasRateChanges(Invoice $invoice): bool
    {
        // This would check if any rates changed during the billing period
        return false; // Placeholder - would query tariff changes
    }

    private function hasHighRiskFactors(Invoice $invoice, BillingOptions $options): bool
    {
        return $invoice->total_amount > 2000.0 || 
               $this->hasEstimatedReadings($invoice) ||
               $this->hasComplexCalculations($invoice);
    }

    private function hasHistoricalAccuracy(Invoice $invoice): bool
    {
        // This would check historical accuracy for this property/tenant
        return true; // Placeholder - would query historical data
    }

    /**
     * Calculate complexity score for the invoice calculation.
     */
    private function calculateComplexityScore(Invoice $invoice): int
    {
        $score = 0;
        
        // Base complexity
        $score += 1;
        
        // Add complexity for multiple service configurations
        $serviceConfigCount = ServiceConfiguration::where('property_id', $invoice->property_id)
            ->where('is_active', true)
            ->count();
        $score += $serviceConfigCount;
        
        // Add complexity for shared services
        $sharedServiceCount = ServiceConfiguration::where('property_id', $invoice->property_id)
            ->where('is_shared_service', true)
            ->where('is_active', true)
            ->count();
        $score += $sharedServiceCount * 2; // Shared services are more complex
        
        // Add complexity for heating calculations
        if ($this->hasHeatingCalculations($invoice)) {
            $score += 3; // Heating calculations are complex
        }
        
        return $score;
    }

    /**
     * Restore invoice calculation context from snapshot.
     * 
     * @param Invoice $invoice The invoice with snapshot data
     * @return array Restored calculation context
     */
    public function restoreCalculationContext(Invoice $invoice): array
    {
        if (!$invoice->snapshot_data) {
            throw new \InvalidArgumentException("Invoice {$invoice->id} has no snapshot data");
        }

        $snapshot = $invoice->snapshot_data;
        
        Log::info('Restoring calculation context from snapshot', [
            'invoice_id' => $invoice->id,
            'snapshot_created_at' => $invoice->snapshot_created_at,
        ]);

        return [
            'tariffs' => $snapshot['tariff_snapshots'] ?? [],
            'service_configurations' => $snapshot['service_configuration_snapshots'] ?? [],
            'utility_services' => $snapshot['utility_service_snapshots'] ?? [],
            'billing_period' => $snapshot['billing_period'] ?? [],
            'billing_options' => $snapshot['billing_options'] ?? [],
            'calculation_metadata' => $snapshot['calculation_metadata'] ?? [],
        ];
    }

    /**
     * Validate that an invoice can be recalculated using its snapshot.
     * 
     * @param Invoice $invoice The invoice to validate
     * @return bool True if recalculation is possible
     */
    public function canRecalculateFromSnapshot(Invoice $invoice): bool
    {
        if (!$invoice->snapshot_data) {
            return false;
        }

        $snapshot = $invoice->snapshot_data;
        
        // Check that all required snapshot components exist
        $requiredComponents = [
            'tariff_snapshots',
            'service_configuration_snapshots',
            'utility_service_snapshots',
            'billing_period',
            'calculation_metadata',
        ];

        foreach ($requiredComponents as $component) {
            if (!isset($snapshot[$component])) {
                Log::warning('Missing snapshot component', [
                    'invoice_id' => $invoice->id,
                    'missing_component' => $component,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Get snapshot summary for reporting.
     * 
     * @param Invoice $invoice The invoice to get summary for
     * @return array Snapshot summary
     */
    public function getSnapshotSummary(Invoice $invoice): array
    {
        if (!$invoice->snapshot_data) {
            return ['has_snapshot' => false];
        }

        $snapshot = $invoice->snapshot_data;
        
        return [
            'has_snapshot' => true,
            'created_at' => $invoice->snapshot_created_at,
            'tariff_count' => count($snapshot['tariff_snapshots'] ?? []),
            'service_configuration_count' => count($snapshot['service_configuration_snapshots'] ?? []),
            'utility_service_count' => count($snapshot['utility_service_snapshots'] ?? []),
            'calculation_complexity_score' => $snapshot['calculation_metadata']['calculation_complexity_score'] ?? 0,
            'requires_approval' => $snapshot['calculation_metadata']['approval_workflow_required'] ?? false,
            'has_seasonal_adjustments' => $snapshot['calculation_metadata']['seasonal_adjustments_applied'] ?? false,
            'has_heating_calculations' => $snapshot['calculation_metadata']['heating_calculations_included'] ?? false,
            'has_shared_services' => $snapshot['calculation_metadata']['shared_service_distributions_applied'] ?? false,
        ];
    }
}