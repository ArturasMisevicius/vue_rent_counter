<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Dashboard;

use App\Enums\AssignmentScope;
use App\Enums\BillingMethod;
use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoiceStatus;
use App\Enums\KycVerificationStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MoveOutProcessStatus;
use App\Enums\PortalAccessStatus;
use App\Enums\RentalContractStatus;
use App\Enums\ServiceConfigurationStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Superadmin\AuditLogs\AuditLogTablePresenter;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\ExtraCharge;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MoveOutProcess;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Models\UserKycProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class BuildAdminAttentionDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function handle(int $organizationId, int $userId, ?int $currentBillingPeriodId = null): AdminAttentionDashboardData
    {
        $user = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale'])
            ->findOrFail($userId);

        $organization = Organization::query()
            ->select(['id', 'name'])
            ->findOrFail($organizationId);

        if (! $this->canSeeOrganization($user, $organizationId)) {
            abort(403);
        }

        $period = $this->currentBillingPeriod($organizationId, $currentBillingPeriodId);
        $visibility = $this->widgetVisibility($user);
        $billingCounts = $this->billingCounts($organizationId, $period);
        $readingCounts = $this->readingCounts($organizationId, $period);
        $tenantCounts = $this->tenantCounts($organizationId);
        $configurationCounts = $this->configurationCounts($organizationId);
        $contractCounts = $this->contractCounts($organizationId);
        $documentCounts = $this->documentCounts($organizationId);
        $moveOutCounts = $this->moveOutCounts($organizationId, $period);
        $dataIntegrityCounts = $this->dataIntegrityCounts($organizationId, $period);
        $counts = [
            ...$billingCounts,
            ...$readingCounts,
            ...$tenantCounts,
            ...$configurationCounts,
            ...$contractCounts,
            ...$documentCounts,
            ...$moveOutCounts,
            ...$dataIntegrityCounts,
        ];

        $needsActionItems = $this->needsActionItems(
            $user,
            $billingCounts,
            $readingCounts,
            $tenantCounts,
            $configurationCounts,
            $contractCounts,
            $documentCounts,
            $moveOutCounts,
            $dataIntegrityCounts,
            $visibility,
        );

        return new AdminAttentionDashboardData(
            organization: [
                'id' => (int) $organization->id,
                'name' => (string) $organization->name,
            ],
            currentBillingPeriod: $this->periodData($period),
            billingCompletion: $this->billingCompletion($billingCounts),
            topCards: $this->topCards($billingCounts, $tenantCounts, $configurationCounts, $contractCounts, $visibility),
            billingCards: $visibility['billing'] ? $this->billingCards($billingCounts) : [],
            tenantOnboardingCards: $visibility['tenant_onboarding'] ? $this->tenantOnboardingCards($tenantCounts) : [],
            configurationHealthCards: $visibility['configuration_health'] ? $this->configurationHealthCards($configurationCounts) : [],
            contractCards: $visibility['contracts'] ? $this->contractCards($contractCounts) : [],
            documentCards: $visibility['documents'] ? $this->documentCards($documentCounts) : [],
            moveOutCards: $visibility['move_outs'] ? $this->moveOutCards($moveOutCounts) : [],
            dataIntegrityCards: $visibility['data_integrity'] ? $this->dataIntegrityCards($dataIntegrityCounts) : [],
            needsActionItems: $needsActionItems,
            billingProgressSteps: $this->billingProgressSteps($billingCounts),
            recentActivity: $this->recentActivity($organizationId),
            widgetVisibility: $visibility,
            counts: $counts,
            emptyStateTitle: $this->emptyStateTitle($tenantCounts['tenants_total'], $needsActionItems),
            emptyStateDescription: $this->emptyStateDescription($tenantCounts['tenants_total'], $needsActionItems),
        );
    }

    private function canSeeOrganization(User $user, int $organizationId): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && (int) $user->organization_id === $organizationId;
    }

    private function currentBillingPeriod(int $organizationId, ?int $billingPeriodId): ?BillingPeriod
    {
        if ($billingPeriodId !== null) {
            $period = BillingPeriod::query()
                ->select(['id', 'organization_id', 'name', 'starts_at', 'ends_at', 'reading_submission_deadline', 'invoice_generation_date', 'payment_due_date'])
                ->forOrganization($organizationId)
                ->find($billingPeriodId);

            if ($period instanceof BillingPeriod) {
                return $period;
            }
        }

        $today = today()->toDateString();

        $active = BillingPeriod::query()
            ->select(['id', 'organization_id', 'name', 'starts_at', 'ends_at', 'reading_submission_deadline', 'invoice_generation_date', 'payment_due_date'])
            ->forOrganization($organizationId)
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->orderByDesc('starts_at')
            ->first();

        if ($active instanceof BillingPeriod) {
            return $active;
        }

        return BillingPeriod::query()
            ->select(['id', 'organization_id', 'name', 'starts_at', 'ends_at', 'reading_submission_deadline', 'invoice_generation_date', 'payment_due_date'])
            ->forOrganization($organizationId)
            ->orderByDesc('starts_at')
            ->first();
    }

    /**
     * @return array<string, bool>
     */
    private function widgetVisibility(User $user): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperadmin();

        if ($isAdmin) {
            return [
                'billing' => true,
                'readings' => true,
                'tenant_onboarding' => true,
                'tenants' => true,
                'configuration_health' => true,
                'configuration' => true,
                'contracts' => true,
                'documents' => true,
                'move_outs' => true,
                'data_integrity' => true,
                'subscription' => true,
            ];
        }

        if (! $user->isManager() || $user->organization_id === null) {
            return [
                'billing' => false,
                'readings' => false,
                'tenant_onboarding' => false,
                'tenants' => false,
                'configuration_health' => false,
                'configuration' => false,
                'contracts' => false,
                'documents' => false,
                'move_outs' => false,
                'data_integrity' => false,
                'subscription' => false,
            ];
        }

        $organization = Organization::query()
            ->select(['id', 'name'])
            ->find($user->organization_id);

        if (! $organization instanceof Organization) {
            return [
                'billing' => false,
                'readings' => false,
                'tenant_onboarding' => false,
                'tenants' => false,
                'configuration_health' => false,
                'configuration' => false,
                'contracts' => false,
                'documents' => false,
                'move_outs' => false,
                'data_integrity' => false,
                'subscription' => false,
            ];
        }

        $matrix = app(ManagerPermissionService::class)->getMatrix($user, $organization);
        $billing = $this->hasAnyManagerPermission($matrix, ['billing', 'invoices', 'meter_readings', 'extra_charges']);
        $tenants = $this->hasAnyManagerPermission($matrix, ['tenants']);
        $configuration = $this->hasAnyManagerPermission($matrix, ['service_configurations', 'tariffs', 'providers', 'utility_services']);
        $contracts = $this->hasAnyManagerPermission($matrix, ['rental_contracts']);
        $readings = $this->hasAnyManagerPermission($matrix, ['meter_readings', 'billing']);

        return [
            'billing' => $billing,
            'readings' => $readings,
            'tenant_onboarding' => $tenants,
            'tenants' => $tenants,
            'configuration_health' => $configuration,
            'configuration' => $configuration,
            'contracts' => $contracts,
            'documents' => $tenants || $contracts,
            'move_outs' => $tenants || $billing || $contracts,
            'data_integrity' => $billing || $readings || $configuration,
            'subscription' => false,
        ];
    }

    /**
     * @param  array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>  $matrix
     * @param  list<string>  $resources
     */
    private function hasAnyManagerPermission(array $matrix, array $resources): bool
    {
        foreach ($resources as $resource) {
            $permissions = $matrix[$resource] ?? null;

            if ($permissions === null) {
                continue;
            }

            if (($permissions['can_create'] ?? false) || ($permissions['can_edit'] ?? false) || ($permissions['can_delete'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, int>
     */
    private function billingCounts(int $organizationId, ?BillingPeriod $period): array
    {
        $invoices = $this->periodInvoices($organizationId, $period);

        return [
            'invoices_waiting_for_readings' => (clone $invoices)
                ->where('status', InvoiceStatus::DRAFT)
                ->where('automation_level', 'reading_request')
                ->whereIn('approval_status', ['waiting_for_readings', 'pending'])
                ->count(),
            'invoices_with_submitted_readings' => (clone $invoices)
                ->where('automation_level', 'reading_request')
                ->whereIn('approval_status', ['readings_submitted', 'submitted_readings'])
                ->count(),
            'invoices_ready_for_review' => (clone $invoices)
                ->where('status', InvoiceStatus::DRAFT)
                ->where('approval_status', 'ready_for_review')
                ->count(),
            'invoices_with_configuration_errors' => (clone $invoices)
                ->whereHas('property.serviceConfigurations', fn (Builder $query): Builder => $query->where('status', ServiceConfigurationStatus::CONFIGURATION_ERROR))
                ->count(),
            'invoices_overdue' => Invoice::query()
                ->select(['id', 'organization_id', 'status', 'due_date', 'billing_period_end'])
                ->forOrganization($organizationId)
                ->whereOverdueAsOf()
                ->count(),
            'invoices_sent' => (clone $invoices)
                ->whereHas('emailLogs')
                ->count(),
            'invoices_paid' => (clone $invoices)
                ->where('status', InvoiceStatus::PAID)
                ->count(),
            'total_invoices' => (clone $invoices)->count(),
            'draft_invoices' => (clone $invoices)->where('status', InvoiceStatus::DRAFT)->count(),
            'approved_invoices' => (clone $invoices)->where('approval_status', 'approved')->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function readingCounts(int $organizationId, ?BillingPeriod $period): array
    {
        $periodStart = $this->periodStart($period);
        $periodEnd = $this->periodEnd($period);
        $periodReadings = MeterReading::query()
            ->select(['id', 'organization_id', 'meter_id', 'reading_date', 'validation_status'])
            ->forOrganization($organizationId)
            ->betweenDates($periodStart, $periodEnd);

        return [
            'missing_readings' => Meter::query()
                ->select(['id', 'organization_id'])
                ->forOrganization($organizationId)
                ->active()
                ->whereDoesntHave('readings', fn (Builder $query): Builder => $this->periodReadings($query, $periodStart, $periodEnd))
                ->count(),
            'submitted_readings' => (clone $periodReadings)->where('validation_status', MeterReadingValidationStatus::PENDING)->count(),
            'approved_readings' => (clone $periodReadings)->where('validation_status', MeterReadingValidationStatus::VALID)->count(),
            'rejected_readings' => (clone $periodReadings)->where('validation_status', MeterReadingValidationStatus::REJECTED)->count(),
            'readings_with_warnings' => (clone $periodReadings)->where('validation_status', MeterReadingValidationStatus::FLAGGED)->count(),
            'duplicate_readings' => $this->duplicateReadingCount($organizationId, $periodStart, $periodEnd),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function tenantCounts(int $organizationId): array
    {
        $tenants = User::query()
            ->select(['id', 'organization_id', 'role', 'status', 'portal_access_enabled'])
            ->forOrganization($organizationId)
            ->tenants();

        return [
            'tenants_total' => (clone $tenants)->count(),
            'properties_without_tenant' => Property::query()
                ->select(['id', 'organization_id'])
                ->forOrganization($organizationId)
                ->whereDoesntHave('currentAssignment')
                ->count(),
            'tenants_not_invited' => (clone $tenants)
                ->where('portal_access_enabled', false)
                ->whereDoesntHave('tenantInvitations')
                ->count(),
            'tenants_invited' => OrganizationInvitation::query()
                ->forOrganization($organizationId)
                ->where('role', UserRole::TENANT)
                ->pending()
                ->count(),
            'tenants_invitation_expired' => OrganizationInvitation::query()
                ->forOrganization($organizationId)
                ->where('role', UserRole::TENANT)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '<', now())
                ->count(),
            'tenants_portal_active' => (clone $tenants)
                ->where('status', UserStatus::ACTIVE)
                ->where('portal_access_enabled', true)
                ->count(),
            'tenants_portal_disabled' => (clone $tenants)
                ->where('status', UserStatus::ACTIVE)
                ->where('portal_access_enabled', false)
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function configurationCounts(int $organizationId): array
    {
        $active = ServiceConfiguration::query()
            ->select(['id', 'organization_id', 'property_id', 'billing_method', 'fixed_amount', 'tenant_visible', 'tenant_visible_description', 'status', 'tariff_id', 'is_active', 'assignment_scope'])
            ->forOrganization($organizationId)
            ->where('is_active', true);

        $missingTariffs = (clone $active)
            ->whereNull('tariff_id')
            ->whereIn('billing_method', [
                BillingMethod::METER_BASED,
                BillingMethod::FIXED_MONTHLY,
                BillingMethod::PERCENTAGE,
                BillingMethod::FORMULA_BASED,
            ])
            ->count();
        $withoutAssignments = (clone $active)
            ->whereNull('property_id')
            ->whereIn('assignment_scope', [AssignmentScope::PROPERTY, AssignmentScope::TENANT])
            ->count();
        $meterServicesWithoutMeters = (clone $active)
            ->where('billing_method', BillingMethod::METER_BASED)
            ->whereDoesntHave('property.meters')
            ->count();
        $fixedWithoutAmount = (clone $active)
            ->where('billing_method', BillingMethod::FIXED_MONTHLY)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('fixed_amount')
                    ->orWhere('fixed_amount', '<=', 0);
            })
            ->count();
        $visibleWithoutDescription = (clone $active)
            ->where('tenant_visible', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('tenant_visible_description')
                    ->orWhere('tenant_visible_description', '');
            })
            ->count();
        $statusErrors = (clone $active)
            ->where('status', ServiceConfigurationStatus::CONFIGURATION_ERROR)
            ->count();

        return [
            'services_with_missing_tariff' => $missingTariffs,
            'services_without_assignment' => $withoutAssignments,
            'meter_services_without_meters' => $meterServicesWithoutMeters,
            'fixed_services_without_amount' => $fixedWithoutAmount,
            'tenant_visible_services_without_description' => $visibleWithoutDescription,
            'configuration_errors_total' => $statusErrors,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function contractCounts(int $organizationId): array
    {
        return [
            'tenants_without_contract' => User::query()
                ->select(['id', 'organization_id', 'role'])
                ->forOrganization($organizationId)
                ->tenants()
                ->whereDoesntHave('rentalContracts', fn (Builder $query): Builder => $query->active())
                ->count(),
            'contracts_active' => RentalContract::query()
                ->forOrganization($organizationId)
                ->active()
                ->count(),
            'contracts_expiring_30_days' => RentalContract::query()
                ->forOrganization($organizationId)
                ->active()
                ->whereDate('end_date', '>=', today())
                ->whereDate('end_date', '<=', today()->addDays(30))
                ->count(),
            'contracts_expiring_14_days' => RentalContract::query()
                ->forOrganization($organizationId)
                ->active()
                ->whereDate('end_date', '>=', today())
                ->whereDate('end_date', '<=', today()->addDays(14))
                ->count(),
            'contracts_expired' => RentalContract::query()
                ->forOrganization($organizationId)
                ->where(function (Builder $query): void {
                    $query
                        ->where('status', RentalContractStatus::EXPIRED)
                        ->orWhere(fn (Builder $activeQuery): Builder => $activeQuery
                            ->where('status', RentalContractStatus::ACTIVE)
                            ->whereDate('end_date', '<', today()));
                })
                ->count(),
            'contracts_terminated' => RentalContract::query()
                ->forOrganization($organizationId)
                ->where('status', RentalContractStatus::TERMINATED)
                ->count(),
            'moved_out_tenants_with_active_contracts' => User::query()
                ->forOrganization($organizationId)
                ->tenants()
                ->where('tenant_status', TenantStatus::MOVED_OUT)
                ->whereHas('rentalContracts', fn (Builder $query): Builder => $query->active())
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function documentCounts(int $organizationId): array
    {
        return [
            'kyc_pending_review' => UserKycProfile::query()
                ->where('organization_id', $organizationId)
                ->where('verification_status', KycVerificationStatus::PENDING)
                ->count(),
            'rejected_kyc_waiting_tenant_action' => UserKycProfile::query()
                ->where('organization_id', $organizationId)
                ->where('verification_status', KycVerificationStatus::REJECTED)
                ->count(),
            'documents_expiring_soon' => $this->documentsExpiringSoonCount($organizationId),
            'documents_uploaded_recently' => Attachment::query()
                ->forOrganization($organizationId)
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'internal_only_important_documents' => Attachment::query()
                ->forOrganization($organizationId)
                ->where('tenant_visible', false)
                ->whereNotNull('document_type')
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function dataIntegrityCounts(int $organizationId, ?BillingPeriod $period): array
    {
        return [
            'duplicate_active_readings' => $this->duplicateReadingCount(
                $organizationId,
                $this->periodStart($period),
                $this->periodEnd($period),
            ),
            'duplicate_invoices' => $this->duplicateInvoiceCount($organizationId),
            'duplicate_invoice_items' => $this->duplicateInvoiceItemCount($organizationId),
            'orphan_readings' => MeterReading::query()
                ->forOrganization($organizationId)
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('meter_id')
                        ->orWhereNull('property_id');
                })
                ->count(),
            'orphan_invoice_items' => InvoiceItem::query()
                ->whereDoesntHave('invoice')
                ->count(),
            'orphan_documents' => Attachment::query()
                ->forOrganization($organizationId)
                ->where(function (Builder $query): void {
                    $query
                        ->whereNull('attachable_type')
                        ->orWhereNull('attachable_id');
                })
                ->count(),
            'charges_included_twice' => $this->chargesIncludedTwiceCount($organizationId),
            'payments_without_invoice' => InvoicePayment::query()
                ->forOrganizationValue($organizationId)
                ->whereDoesntHave('invoice')
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function moveOutCounts(int $organizationId, ?BillingPeriod $period): array
    {
        $periodStart = $this->periodStart($period);
        $periodEnd = $this->periodEnd($period);

        $openProcesses = MoveOutProcess::query()
            ->select(['id', 'organization_id', 'status', 'move_out_date', 'final_readings_required', 'final_readings_completed_at', 'final_invoice_id'])
            ->forOrganization($organizationId)
            ->open();

        return [
            'move_outs_scheduled_this_month' => (clone $openProcesses)
                ->whereDate('move_out_date', '>=', $periodStart->toDateString())
                ->whereDate('move_out_date', '<=', $periodEnd->toDateString())
                ->count(),
            'final_readings_pending' => (clone $openProcesses)
                ->where('final_readings_required', true)
                ->whereNull('final_readings_completed_at')
                ->count(),
            'final_invoices_pending' => (clone $openProcesses)
                ->whereNull('final_invoice_id')
                ->count(),
            'properties_becoming_vacant' => (clone $openProcesses)
                ->whereDate('move_out_date', '>=', today()->toDateString())
                ->whereDate('move_out_date', '<=', today()->addDays(30)->toDateString())
                ->count(),
            'moved_out_tenants_with_unpaid_balance' => User::query()
                ->select(['id', 'organization_id', 'role', 'tenant_status'])
                ->forOrganization($organizationId)
                ->tenants()
                ->where('tenant_status', TenantStatus::MOVED_OUT)
                ->whereHas('tenantInvoices', fn (Builder $query): Builder => $query
                    ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::VOID]))
                ->count(),
        ];
    }

    private function duplicateReadingCount(int $organizationId, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): int
    {
        return MeterReading::query()
            ->select(['id', 'organization_id', 'meter_id', 'reading_date', 'validation_status'])
            ->forOrganization($organizationId)
            ->betweenDates($periodStart, $periodEnd)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::PENDING,
                MeterReadingValidationStatus::VALID,
                MeterReadingValidationStatus::FLAGGED,
            ])
            ->get()
            ->groupBy(fn (MeterReading $reading): string => implode(':', [
                $reading->meter_id,
                $reading->reading_date?->toDateString(),
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->count();
    }

    private function duplicateInvoiceCount(int $organizationId): int
    {
        return Invoice::query()
            ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'billing_period_start', 'billing_period_end', 'status'])
            ->forOrganization($organizationId)
            ->where('status', '!=', InvoiceStatus::VOID)
            ->get()
            ->groupBy(fn (Invoice $invoice): string => implode(':', [
                $invoice->property_id,
                $invoice->tenant_user_id,
                $invoice->billing_period_start?->toDateString(),
                $invoice->billing_period_end?->toDateString(),
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->count();
    }

    private function duplicateInvoiceItemCount(int $organizationId): int
    {
        return InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id', 'service_configuration_id', 'utility_service_id'])
            ->whereHas('invoice', fn (Builder $query): Builder => $query->forOrganization($organizationId))
            ->get()
            ->groupBy(fn (InvoiceItem $item): string => implode(':', [
                $item->invoice_id,
                $item->source_type?->value,
                $item->source_id,
                $item->service_configuration_id,
                $item->utility_service_id,
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->count();
    }

    private function chargesIncludedTwiceCount(int $organizationId): int
    {
        return InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id'])
            ->where('source_type', InvoiceItemSourceType::EXTRA_CHARGE)
            ->whereNotNull('source_id')
            ->whereHas('invoice', fn (Builder $query): Builder => $query->forOrganization($organizationId))
            ->get()
            ->groupBy('source_id')
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->count();
    }

    private function documentsExpiringSoonCount(int $organizationId): int
    {
        return Attachment::query()
            ->select(['id', 'organization_id', 'metadata'])
            ->forOrganization($organizationId)
            ->whereNotNull('metadata')
            ->get()
            ->filter(function (Attachment $attachment): bool {
                $expiresAt = data_get($attachment->metadata, 'expires_at');

                if (blank($expiresAt)) {
                    return false;
                }

                try {
                    $date = CarbonImmutable::parse((string) $expiresAt)->startOfDay();
                } catch (\Throwable) {
                    return false;
                }

                return $date->betweenIncluded(today(), today()->addDays(30));
            })
            ->count();
    }

    private function periodInvoices(int $organizationId, ?BillingPeriod $period): Builder
    {
        $periodStart = $this->periodStart($period);
        $periodEnd = $this->periodEnd($period);

        return Invoice::query()
            ->select(['id', 'organization_id', 'billing_period_id', 'property_id', 'status', 'billing_period_start', 'billing_period_end', 'due_date', 'approval_status'])
            ->forOrganization($organizationId)
            ->where(function (Builder $query) use ($period, $periodStart, $periodEnd): void {
                if ($period instanceof BillingPeriod) {
                    $query->where('billing_period_id', $period->id);
                }

                $query->orWhere(function (Builder $dateQuery) use ($periodStart, $periodEnd): void {
                    $dateQuery
                        ->whereDate('billing_period_start', $periodStart->toDateString())
                        ->whereDate('billing_period_end', $periodEnd->toDateString());
                });
            });
    }

    private function periodReadings(Builder $query, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): Builder
    {
        return $query
            ->whereDate('reading_date', '>=', $periodStart->toDateString())
            ->whereDate('reading_date', '<=', $periodEnd->toDateString());
    }

    private function periodStart(?BillingPeriod $period): CarbonImmutable
    {
        return CarbonImmutable::parse($period?->starts_at ?? now()->startOfMonth())->startOfDay();
    }

    private function periodEnd(?BillingPeriod $period): CarbonImmutable
    {
        return CarbonImmutable::parse($period?->ends_at ?? now()->endOfMonth())->endOfDay();
    }

    /**
     * @return array<string, mixed>
     */
    private function periodData(?BillingPeriod $period): array
    {
        $start = $this->periodStart($period);
        $end = $this->periodEnd($period);

        return [
            'id' => $period?->id,
            'name' => $period?->name ?? $start->translatedFormat('F Y'),
            'label' => __('dashboard.organization_invoice_period', [
                'from' => $start->translatedFormat('F Y'),
                'to' => $end->translatedFormat('F Y'),
            ]),
            'starts_at' => $start->toDateString(),
            'ends_at' => $end->toDateString(),
        ];
    }

    private function billingCompletion(array $billingCounts): int
    {
        $total = max((int) $billingCounts['total_invoices'], 1);
        $complete = (int) $billingCounts['invoices_sent'] + (int) $billingCounts['invoices_paid'];

        return min(100, (int) round(($complete / $total) * 100));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topCards(array $billingCounts, array $tenantCounts, array $configurationCounts, array $contractCounts, array $visibility): array
    {
        return array_values(array_filter([
            $visibility['billing'] ? $this->card('waiting_readings', __('dashboard.attention.cards.waiting_readings'), $billingCounts['invoices_waiting_for_readings'], $this->billingReviewUrl('waiting_for_readings'), 'warning') : null,
            $visibility['billing'] ? $this->card('submitted_readings', __('dashboard.attention.cards.submitted_readings'), $billingCounts['invoices_with_submitted_readings'], $this->billingReviewUrl('submitted_readings'), 'info') : null,
            $visibility['billing'] ? $this->card('ready_review', __('dashboard.attention.cards.ready_review'), $billingCounts['invoices_ready_for_review'], $this->billingReviewUrl('ready_for_review'), 'success') : null,
            $visibility['billing'] ? $this->card('overdue_invoices', __('dashboard.attention.cards.overdue_invoices'), $billingCounts['invoices_overdue'], $this->resourceUrl('filament.admin.resources.invoices.index', ['status' => InvoiceStatus::OVERDUE->value]), 'danger') : null,
            $visibility['configuration_health'] ? $this->card('configuration_errors', __('dashboard.attention.cards.configuration_errors'), $configurationCounts['configuration_errors_total'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'configuration_errors']), 'danger') : null,
            $visibility['contracts'] ? $this->card('contracts_expiring', __('dashboard.attention.cards.contracts_expiring'), $contractCounts['contracts_expiring_30_days'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'contracts_expiring']), 'warning') : null,
            $visibility['tenant_onboarding'] ? $this->card('properties_without_tenant', __('dashboard.attention.cards.properties_without_tenant'), $tenantCounts['properties_without_tenant'], $this->resourceUrl('filament.admin.resources.tenants.create'), 'warning', __('dashboard.attention.actions.create_tenant')) : null,
            $visibility['tenant_onboarding'] ? $this->card('tenants_not_invited', __('dashboard.attention.cards.tenants_not_invited'), $tenantCounts['tenants_not_invited'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::NOT_INVITED->value]), 'warning') : null,
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function billingCards(array $counts): array
    {
        return [
            $this->card('draft_invoices', __('dashboard.attention.cards.draft_invoices'), $counts['draft_invoices'], $this->resourceUrl('filament.admin.resources.invoices.index', ['status' => InvoiceStatus::DRAFT->value]), 'neutral', __('dashboard.attention.actions.view_drafts')),
            $this->card('waiting_for_readings', __('dashboard.attention.cards.waiting_for_readings'), $counts['invoices_waiting_for_readings'], $this->billingReviewUrl('waiting_for_readings'), 'warning', __('dashboard.attention.actions.send_reminders')),
            $this->card('submitted_readings', __('dashboard.attention.cards.submitted_readings'), $counts['invoices_with_submitted_readings'], $this->billingReviewUrl('submitted_readings'), 'info', __('dashboard.attention.actions.review_readings')),
            $this->card('ready_for_review', __('dashboard.attention.cards.ready_for_review'), $counts['invoices_ready_for_review'], $this->billingReviewUrl('ready_for_review'), 'success', __('dashboard.attention.actions.open_review_center')),
            $this->card('sent_invoices', __('dashboard.attention.cards.sent_invoices'), $counts['invoices_sent'], $this->resourceUrl('filament.admin.resources.invoices.index', ['attention' => 'sent']), 'neutral', __('dashboard.attention.actions.view_sent')),
            $this->card('overdue_invoices', __('dashboard.attention.cards.overdue_invoices'), $counts['invoices_overdue'], $this->resourceUrl('filament.admin.resources.invoices.index', ['status' => InvoiceStatus::OVERDUE->value]), 'danger', __('dashboard.attention.actions.view_overdue')),
            $this->card('paid_invoices', __('dashboard.attention.cards.paid_invoices'), $counts['invoices_paid'], $this->resourceUrl('filament.admin.resources.invoices.index', ['status' => InvoiceStatus::PAID->value]), 'success', __('dashboard.attention.actions.view_paid')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tenantOnboardingCards(array $counts): array
    {
        return [
            $this->card('properties_without_tenant', __('dashboard.attention.cards.properties_without_tenant'), $counts['properties_without_tenant'], $this->resourceUrl('filament.admin.resources.tenants.create'), 'warning', __('dashboard.attention.actions.create_tenant')),
            $this->card('tenants_not_invited', __('dashboard.attention.cards.tenants_not_invited'), $counts['tenants_not_invited'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::NOT_INVITED->value]), 'warning', __('dashboard.attention.actions.send_invitations')),
            $this->card('pending_invitations', __('dashboard.attention.cards.pending_invitations'), $counts['tenants_invited'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::INVITED->value]), 'info', __('dashboard.attention.actions.view')),
            $this->card('expired_invitations', __('dashboard.attention.cards.expired_invitations'), $counts['tenants_invitation_expired'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::INVITATION_EXPIRED->value]), 'danger', __('dashboard.attention.actions.resend')),
            $this->card('active_portal_users', __('dashboard.attention.cards.active_portal_users'), $counts['tenants_portal_active'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::ACTIVE->value]), 'success', __('dashboard.attention.actions.view_tenants')),
            $this->card('disabled_portal_access', __('dashboard.attention.cards.disabled_portal_access'), $counts['tenants_portal_disabled'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::DISABLED->value]), 'warning', __('dashboard.attention.actions.review')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function configurationHealthCards(array $counts): array
    {
        return [
            $this->card('missing_tariffs', __('dashboard.attention.cards.missing_tariffs'), $counts['services_with_missing_tariff'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'missing_tariff']), 'danger', __('dashboard.attention.actions.fix_tariffs')),
            $this->card('services_without_assignment', __('dashboard.attention.cards.services_without_assignments'), $counts['services_without_assignment'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'without_assignment']), 'warning', __('dashboard.attention.actions.assign_services')),
            $this->card('meter_services_without_meters', __('dashboard.attention.cards.meter_services_without_meters'), $counts['meter_services_without_meters'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'meter_without_meters']), 'danger', __('dashboard.attention.actions.fix_meters')),
            $this->card('fixed_services_without_amount', __('dashboard.attention.cards.fixed_services_without_amount'), $counts['fixed_services_without_amount'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'fixed_without_amount']), 'warning', __('dashboard.attention.actions.fix_amounts')),
            $this->card('tenant_visible_without_description', __('dashboard.attention.cards.tenant_visible_services_without_description'), $counts['tenant_visible_services_without_description'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'tenant_description_missing']), 'neutral', __('dashboard.attention.actions.edit_visibility')),
            $this->card('configuration_errors_total', __('dashboard.attention.cards.configuration_errors'), $counts['configuration_errors_total'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'configuration_errors']), 'danger', __('dashboard.attention.actions.fix_now')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contractCards(array $counts): array
    {
        return [
            $this->card('tenants_without_contract', __('dashboard.attention.cards.tenants_without_contract'), $counts['tenants_without_contract'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'no_contract']), 'warning', __('dashboard.attention.actions.add_contracts')),
            $this->card('contracts_expiring_30_days', __('dashboard.attention.cards.contracts_expiring_30_days'), $counts['contracts_expiring_30_days'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'contracts_expiring_30']), 'warning', __('dashboard.attention.actions.review')),
            $this->card('contracts_expiring_14_days', __('dashboard.attention.cards.contracts_expiring_14_days'), $counts['contracts_expiring_14_days'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'contracts_expiring_14']), 'danger', __('dashboard.attention.actions.review')),
            $this->card('contracts_expired', __('dashboard.attention.cards.expired_contracts'), $counts['contracts_expired'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'contracts_expired']), 'danger', __('dashboard.attention.actions.renew_or_terminate')),
            $this->card('moved_out_active_contracts', __('dashboard.attention.cards.moved_out_tenants_with_active_contracts'), $counts['moved_out_tenants_with_active_contracts'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'moved_out_active_contracts']), 'danger', __('dashboard.attention.actions.resolve')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documentCards(array $counts): array
    {
        return [
            $this->card('kyc_pending_review', __('dashboard.attention.cards.kyc_pending_review'), $counts['kyc_pending_review'], $this->resourceUrl('filament.admin.resources.user-kyc-profiles.index', ['verification_status' => KycVerificationStatus::PENDING->value]), 'warning', __('dashboard.attention.actions.review_kyc')),
            $this->card('rejected_kyc', __('dashboard.attention.cards.rejected_kyc'), $counts['rejected_kyc_waiting_tenant_action'], $this->resourceUrl('filament.admin.resources.user-kyc-profiles.index', ['verification_status' => KycVerificationStatus::REJECTED->value]), 'warning', __('dashboard.attention.actions.view')),
            $this->card('documents_expiring_soon', __('dashboard.attention.cards.documents_expiring_soon'), $counts['documents_expiring_soon'], $this->resourceUrl('filament.admin.resources.user-kyc-profiles.index', ['attention' => 'documents_expiring']), 'danger', __('dashboard.attention.actions.open_documents')),
            $this->card('recent_uploads', __('dashboard.attention.cards.recent_uploads'), $counts['documents_uploaded_recently'], $this->resourceUrl('filament.admin.resources.user-kyc-profiles.index', ['attention' => 'recent_uploads']), 'info', __('dashboard.attention.actions.view_uploads')),
            $this->card('internal_only_documents', __('dashboard.attention.cards.internal_only_documents'), $counts['internal_only_important_documents'], $this->resourceUrl('filament.admin.resources.user-kyc-profiles.index', ['attention' => 'internal_documents']), 'neutral', __('dashboard.attention.actions.review')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function moveOutCards(array $counts): array
    {
        return [
            $this->card('move_outs_scheduled_this_month', __('dashboard.attention.cards.move_outs_scheduled_this_month'), $counts['move_outs_scheduled_this_month'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'move_outs_scheduled']), 'warning', __('dashboard.attention.actions.open_move_outs')),
            $this->card('final_readings_pending', __('dashboard.attention.cards.final_readings_pending'), $counts['final_readings_pending'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'final_readings_pending']), 'danger', __('dashboard.attention.actions.record_readings')),
            $this->card('final_invoices_pending', __('dashboard.attention.cards.final_invoices_pending'), $counts['final_invoices_pending'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'final_invoices_pending']), 'warning', __('dashboard.attention.actions.generate_final_invoice')),
            $this->card('properties_becoming_vacant', __('dashboard.attention.cards.properties_becoming_vacant'), $counts['properties_becoming_vacant'], $this->resourceUrl('filament.admin.resources.properties.index', ['attention' => 'becoming_vacant']), 'info', __('dashboard.attention.actions.open_properties')),
            $this->card('moved_out_tenants_with_unpaid_balance', __('dashboard.attention.cards.moved_out_tenants_with_unpaid_balance'), $counts['moved_out_tenants_with_unpaid_balance'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'moved_out_unpaid_balance']), 'danger', __('dashboard.attention.actions.review_balance')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dataIntegrityCards(array $counts): array
    {
        return [
            $this->card('duplicate_active_readings', __('dashboard.attention.cards.duplicate_active_readings'), $counts['duplicate_active_readings'], $this->cleanupUrl('duplicate_active_readings'), 'danger', __('dashboard.attention.actions.resolve'), 'blocking'),
            $this->card('duplicate_invoices', __('dashboard.attention.cards.duplicate_invoices'), $counts['duplicate_invoices'], $this->cleanupUrl('duplicate_invoices'), 'danger', __('dashboard.attention.actions.fix'), 'blocking'),
            $this->card('duplicate_invoice_items', __('dashboard.attention.cards.duplicate_invoice_items'), $counts['duplicate_invoice_items'], $this->cleanupUrl('duplicate_invoice_items'), 'danger', __('dashboard.attention.actions.fix'), 'blocking'),
            $this->card('orphan_readings', __('dashboard.attention.cards.orphan_readings'), $counts['orphan_readings'], $this->cleanupUrl('orphan_readings'), 'warning', __('dashboard.attention.actions.review'), 'warning'),
            $this->card('orphan_invoice_items', __('dashboard.attention.cards.orphan_invoice_items'), $counts['orphan_invoice_items'], $this->cleanupUrl('orphan_invoice_items'), 'warning', __('dashboard.attention.actions.review'), 'warning'),
            $this->card('orphan_documents', __('dashboard.attention.cards.orphan_documents'), $counts['orphan_documents'], $this->cleanupUrl('orphan_documents'), 'warning', __('dashboard.attention.actions.review'), 'warning'),
            $this->card('charges_included_twice', __('dashboard.attention.cards.charges_included_twice'), $counts['charges_included_twice'], $this->cleanupUrl('charges_included_twice'), 'danger', __('dashboard.attention.actions.fix'), 'blocking'),
            $this->card('payments_without_invoice', __('dashboard.attention.cards.payments_without_invoice'), $counts['payments_without_invoice'], $this->cleanupUrl('payments_without_invoice'), 'warning', __('dashboard.attention.actions.review'), 'warning'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function needsActionItems(
        User $user,
        array $billingCounts,
        array $readingCounts,
        array $tenantCounts,
        array $configurationCounts,
        array $contractCounts,
        array $documentCounts,
        array $moveOutCounts,
        array $dataIntegrityCounts,
        array $visibility,
    ): array {
        $unreadNotificationsCount = $this->unreadNotificationsCount($user);

        $items = [
            $visibility['configuration_health'] ? $this->priorityItem('configuration_errors', 'high', __('dashboard.attention.needs_action.configuration_errors', ['count' => $configurationCounts['configuration_errors_total']]), $configurationCounts['configuration_errors_total'], $this->resourceUrl('filament.admin.resources.service-configurations.index', ['attention' => 'configuration_errors']), __('dashboard.attention.actions.fix_now')) : null,
            $visibility['data_integrity'] ? $this->priorityItem('duplicate_financial_data', 'high', __('dashboard.attention.needs_action.duplicate_financial_data', ['count' => $dataIntegrityCounts['duplicate_invoice_items'] + $dataIntegrityCounts['duplicate_invoices']]), $dataIntegrityCounts['duplicate_invoice_items'] + $dataIntegrityCounts['duplicate_invoices'], $this->cleanupUrl('duplicate_financial_data'), __('dashboard.attention.actions.fix')) : null,
            $visibility['billing'] ? $this->priorityItem('overdue_invoices', 'high', __('dashboard.attention.needs_action.overdue_invoices', ['count' => $billingCounts['invoices_overdue']]), $billingCounts['invoices_overdue'], $this->resourceUrl('filament.admin.resources.invoices.index', ['status' => InvoiceStatus::OVERDUE->value]), __('dashboard.attention.actions.view_overdue')) : null,
            $visibility['billing'] ? $this->priorityItem('submitted_readings', 'high', __('dashboard.attention.needs_action.submitted_readings', ['count' => $readingCounts['submitted_readings']]), $readingCounts['submitted_readings'], $this->resourceUrl('filament.admin.resources.meter-readings.index', ['validation_status' => MeterReadingValidationStatus::PENDING->value]), __('dashboard.attention.actions.review')) : null,
            $visibility['contracts'] ? $this->priorityItem('expired_contracts', 'high', __('dashboard.attention.needs_action.expired_contracts', ['count' => $contractCounts['contracts_expired']]), $contractCounts['contracts_expired'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'contracts_expired']), __('dashboard.attention.actions.renew_or_terminate')) : null,
            $visibility['move_outs'] ? $this->priorityItem('final_readings_pending', 'high', __('dashboard.attention.needs_action.final_readings_pending', ['count' => $moveOutCounts['final_readings_pending']]), $moveOutCounts['final_readings_pending'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'final_readings_pending']), __('dashboard.attention.actions.record_readings')) : null,
            $visibility['move_outs'] ? $this->priorityItem('moved_out_unpaid_balance', 'high', __('dashboard.attention.needs_action.moved_out_unpaid_balance', ['count' => $moveOutCounts['moved_out_tenants_with_unpaid_balance']]), $moveOutCounts['moved_out_tenants_with_unpaid_balance'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'moved_out_unpaid_balance']), __('dashboard.attention.actions.review_balance')) : null,
            $visibility['tenant_onboarding'] ? $this->priorityItem('expired_invitations', 'high', __('dashboard.attention.needs_action.expired_invitations', ['count' => $tenantCounts['tenants_invitation_expired']]), $tenantCounts['tenants_invitation_expired'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::INVITATION_EXPIRED->value]), __('dashboard.attention.actions.resend')) : null,
            $visibility['tenant_onboarding'] ? $this->priorityItem('properties_without_tenant', 'medium', __('dashboard.attention.needs_action.properties_without_tenant', ['count' => $tenantCounts['properties_without_tenant']]), $tenantCounts['properties_without_tenant'], $this->resourceUrl('filament.admin.resources.tenants.create'), __('dashboard.attention.actions.create_tenant')) : null,
            $visibility['tenant_onboarding'] ? $this->priorityItem('tenants_not_invited', 'medium', __('dashboard.attention.needs_action.tenants_not_invited', ['count' => $tenantCounts['tenants_not_invited']]), $tenantCounts['tenants_not_invited'], $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::NOT_INVITED->value]), __('dashboard.attention.actions.send_invitations')) : null,
            $visibility['contracts'] ? $this->priorityItem('contracts_expiring', 'medium', __('dashboard.attention.needs_action.contracts_expiring', ['count' => $contractCounts['contracts_expiring_30_days']]), $contractCounts['contracts_expiring_30_days'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'contracts_expiring_30']), __('dashboard.attention.actions.open_contracts')) : null,
            $visibility['move_outs'] ? $this->priorityItem('move_outs_scheduled', 'medium', __('dashboard.attention.needs_action.move_outs_scheduled', ['count' => $moveOutCounts['move_outs_scheduled_this_month']]), $moveOutCounts['move_outs_scheduled_this_month'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'move_outs_scheduled']), __('dashboard.attention.actions.open_move_outs')) : null,
            $visibility['move_outs'] ? $this->priorityItem('final_invoices_pending', 'medium', __('dashboard.attention.needs_action.final_invoices_pending', ['count' => $moveOutCounts['final_invoices_pending']]), $moveOutCounts['final_invoices_pending'], $this->resourceUrl('filament.admin.resources.tenants.index', ['attention' => 'final_invoices_pending']), __('dashboard.attention.actions.generate_final_invoice')) : null,
            $visibility['billing'] ? $this->priorityItem('missing_readings', 'medium', __('dashboard.attention.needs_action.missing_readings', ['count' => $readingCounts['missing_readings']]), $readingCounts['missing_readings'], $this->billingReviewUrl('waiting_for_readings'), __('dashboard.attention.actions.send_reminders')) : null,
            $visibility['documents'] ? $this->priorityItem('kyc_pending_review', 'medium', __('dashboard.attention.needs_action.kyc_pending_review', ['count' => $documentCounts['kyc_pending_review']]), $documentCounts['kyc_pending_review'], $this->resourceUrl('filament.admin.resources.user-kyc-profiles.index', ['verification_status' => KycVerificationStatus::PENDING->value]), __('dashboard.attention.actions.review_kyc')) : null,
            $this->priorityItem('unread_notifications', 'low', __('dashboard.attention.needs_action.unread_notifications', ['count' => $unreadNotificationsCount]), $unreadNotificationsCount, $this->resourceUrl('filament.admin.pages.notifications'), __('dashboard.attention.actions.view')),
        ];

        return collect($items)
            ->filter(fn (?array $item): bool => $item !== null && (int) $item['count'] > 0)
            ->sortBy(fn (array $item): int => match ($item['priority']) {
                'high' => 0,
                'medium' => 1,
                default => 2,
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function billingProgressSteps(array $counts): array
    {
        return [
            ['key' => 'waiting', 'label' => __('dashboard.attention.progress.waiting'), 'count' => $counts['invoices_waiting_for_readings'], 'tone' => 'warning'],
            ['key' => 'submitted', 'label' => __('dashboard.attention.progress.submitted'), 'count' => $counts['invoices_with_submitted_readings'], 'tone' => 'info'],
            ['key' => 'review', 'label' => __('dashboard.attention.progress.review'), 'count' => $counts['invoices_ready_for_review'], 'tone' => 'info'],
            ['key' => 'approved', 'label' => __('dashboard.attention.progress.approved'), 'count' => $counts['approved_invoices'], 'tone' => 'success'],
            ['key' => 'sent', 'label' => __('dashboard.attention.progress.sent'), 'count' => $counts['invoices_sent'], 'tone' => 'success'],
            ['key' => 'paid', 'label' => __('dashboard.attention.progress.paid'), 'count' => $counts['invoices_paid'], 'tone' => 'success'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(int $organizationId): array
    {
        return AuditLog::query()
            ->forOrganizationDashboardFeed()
            ->forOrganization($organizationId)
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $record): array => [
                'actor' => AuditLogTablePresenter::actorLabel($record),
                'who' => AuditLogTablePresenter::actorLabel($record),
                'what' => AuditLogTablePresenter::feedLabel($record),
                'when' => $record->occurred_at?->locale(app()->getLocale())->diffForHumans() ?? __('dashboard.not_available'),
                'url' => $this->activityUrl($record),
                'tone' => $this->activityTone($record),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function card(string $key, string $label, int $count, string $url, string $tone, ?string $action = null, string $severity = 'info'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'url' => $url,
            'tone' => $tone,
            'action' => $action ?? __('dashboard.attention.actions.view'),
            'severity' => $severity,
            'icon' => $this->cardIcon($key, $tone),
        ];
    }

    private function cardIcon(string $key, string $tone): string
    {
        return match (true) {
            str_contains($key, 'invoice') || str_contains($key, 'billing') => 'heroicon-m-document-text',
            str_contains($key, 'reading') => 'heroicon-m-clipboard-document-list',
            str_contains($key, 'property') => 'heroicon-m-home',
            str_contains($key, 'tenant') || str_contains($key, 'invitation') || str_contains($key, 'portal') => 'heroicon-m-user-plus',
            str_contains($key, 'contract') => 'heroicon-m-document-check',
            str_contains($key, 'kyc') || str_contains($key, 'document') => 'heroicon-m-paper-clip',
            str_contains($key, 'tariff') || str_contains($key, 'service') || str_contains($key, 'configuration') => 'heroicon-m-wrench-screwdriver',
            str_contains($key, 'duplicate') || str_contains($key, 'orphan') || $tone === 'danger' => 'heroicon-m-shield-exclamation',
            $tone === 'success' => 'heroicon-m-check-circle',
            default => 'heroicon-m-arrow-right-circle',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function priorityItem(string $key, string $priority, string $issue, int $count, string $url, string $action): array
    {
        return [
            'key' => $key,
            'priority' => $priority,
            'priority_label' => __("dashboard.attention.priority.{$priority}"),
            'issue' => $issue,
            'count' => $count,
            'url' => $url,
            'action' => $action,
        ];
    }

    private function resourceUrl(string $routeName, array $query = []): string
    {
        if (! Route::has($routeName)) {
            return Route::has('filament.admin.pages.dashboard') ? route('filament.admin.pages.dashboard') : url('/app');
        }

        return route($routeName, $query);
    }

    private function billingReviewUrl(string $attention): string
    {
        return $this->resourceUrl('filament.admin.pages.billing-review-center', ['attention' => $attention]);
    }

    private function activityUrl(AuditLog $record): string
    {
        if ($record->subject_id === null || blank($record->subject_type)) {
            return $this->resourceUrl('filament.admin.pages.dashboard');
        }

        return match ($record->subject_type) {
            ExtraCharge::class => $this->resourceUrl('filament.admin.resources.extra-charges.view', ['record' => $record->subject_id]),
            Invoice::class => $this->resourceUrl('filament.admin.resources.invoices.view', ['record' => $record->subject_id]),
            InvoiceItem::class => $this->resourceUrl('filament.admin.resources.invoice-items.view', ['record' => $record->subject_id]),
            InvoicePayment::class => $this->resourceUrl('filament.admin.resources.payments.view', ['record' => $record->subject_id]),
            MeterReading::class => $this->resourceUrl('filament.admin.resources.meter-readings.view', ['record' => $record->subject_id]),
            OrganizationInvitation::class => $this->resourceUrl('filament.admin.resources.tenants.index', ['portal_status' => PortalAccessStatus::INVITED->value]),
            Property::class => $this->resourceUrl('filament.admin.resources.properties.view', ['record' => $record->subject_id]),
            ServiceConfiguration::class => $this->resourceUrl('filament.admin.resources.service-configurations.view', ['record' => $record->subject_id]),
            User::class => $this->resourceUrl('filament.admin.resources.tenants.view', ['record' => $record->subject_id]),
            UserKycProfile::class => $this->resourceUrl('filament.admin.resources.user-kyc-profiles.view', ['record' => $record->subject_id]),
            default => $this->resourceUrl('filament.admin.pages.dashboard'),
        };
    }

    private function cleanupUrl(string $attention): string
    {
        return $this->resourceUrl('filament.admin.pages.billing-cleanup-center', ['attention' => $attention]);
    }

    private function unreadNotificationsCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    private function activityTone(AuditLog $record): string
    {
        $label = mb_strtolower(AuditLogTablePresenter::feedLabel($record));

        return match (true) {
            str_contains($label, 'error') || str_contains($label, 'overdue') => 'danger',
            str_contains($label, 'invite') || str_contains($label, 'reading') => 'warning',
            str_contains($label, 'invoice') || str_contains($label, 'payment') => 'success',
            default => 'neutral',
        };
    }

    private function emptyStateTitle(int $tenantCount, array $needsActionItems): string
    {
        if ($tenantCount === 0) {
            return __('dashboard.attention.empty.new_project_title');
        }

        if ($needsActionItems === []) {
            return __('dashboard.attention.empty.no_urgent_actions_title');
        }

        return '';
    }

    private function emptyStateDescription(int $tenantCount, array $needsActionItems): string
    {
        if ($tenantCount === 0) {
            return __('dashboard.attention.empty.new_project_description');
        }

        if ($needsActionItems === []) {
            return __('dashboard.attention.empty.no_urgent_actions_description');
        }

        return '';
    }
}
