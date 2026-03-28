<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\BillingRecord;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Database\Seeder;

class LegacyOperationsFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if ($organization === null) {
            return;
        }

        $property = Property::query()
            ->select(['id', 'organization_id'])
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->first();

        $tenant = User::query()
            ->select(['id', 'organization_id', 'role'])
            ->where('organization_id', $organization->id)
            ->where('role', 'tenant')
            ->orderBy('id')
            ->first();

        $admin = User::query()
            ->select(['id', 'organization_id', 'role'])
            ->where('organization_id', $organization->id)
            ->where('role', 'admin')
            ->orderBy('id')
            ->first();

        if ($property === null || $tenant === null || $admin === null) {
            return;
        }

        $subscription = Subscription::query()->firstOrCreate(
            ['organization_id' => $organization->id],
            [
                'plan' => SubscriptionPlan::BASIC->value,
                'status' => SubscriptionStatus::ACTIVE->value,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonth(),
                'is_trial' => false,
                'property_limit_snapshot' => SubscriptionPlan::BASIC->limits()['properties'],
                'tenant_limit_snapshot' => SubscriptionPlan::BASIC->limits()['tenants'],
                'meter_limit_snapshot' => SubscriptionPlan::BASIC->limits()['meters'],
                'invoice_limit_snapshot' => SubscriptionPlan::BASIC->limits()['invoices'],
            ],
        );

        $invoice = Invoice::query()->updateOrCreate(
            [
                'invoice_number' => 'TEN-OPS-0001',
            ],
            [
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'tenant_user_id' => $tenant->id,
                'billing_period_start' => now()->startOfMonth()->toDateString(),
                'billing_period_end' => now()->endOfMonth()->toDateString(),
                'status' => InvoiceStatus::FINALIZED->value,
                'currency' => 'EUR',
                'total_amount' => 142.75,
                'amount_paid' => 0,
                'paid_amount' => null,
                'due_date' => now()->addDays(14)->toDateString(),
                'finalized_at' => now(),
                'paid_at' => null,
                'payment_reference' => 'RF00TENOPS0001',
                'snapshot_data' => [
                    'seed' => 'legacy_operations_foundation',
                ],
                'snapshot_created_at' => now(),
                'items' => [
                    ['description' => 'Electricity charge', 'total' => 82.35],
                    ['description' => 'Water charge', 'total' => 60.40],
                ],
                'generated_at' => now(),
                'generated_by' => 'legacy_operations_foundation',
                'approval_status' => 'approved',
                'automation_level' => 'manual',
                'approval_deadline' => now()->addDays(2),
                'approval_metadata' => ['approved_in_seed' => true],
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'document_path' => null,
                'notes' => 'Legacy operations foundation demo invoice.',
            ],
        );

        InvoiceItem::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'description' => 'Electricity charge',
            ],
            [
                'quantity' => 457.50,
                'unit' => 'kWh',
                'unit_price' => 0.1800,
                'total' => 82.35,
                'meter_reading_snapshot' => ['source' => 'seed'],
            ],
        );

        InvoiceItem::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'description' => 'Water charge',
            ],
            [
                'quantity' => 27.45,
                'unit' => 'm3',
                'unit_price' => 2.2000,
                'total' => 60.39,
                'meter_reading_snapshot' => ['source' => 'seed'],
            ],
        );

        $meter = Meter::query()->firstOrCreate(
            [
                'identifier' => 'OPS-METER-0001',
            ],
            [
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'name' => 'Operations Demo Meter',
                'type' => 'electricity',
                'status' => 'active',
                'unit' => 'kWh',
                'installed_at' => now()->subYear()->toDateString(),
            ],
        );

        $reading = MeterReading::query()->updateOrCreate(
            [
                'meter_id' => $meter->id,
                'reading_date' => now()->subDay()->toDateString(),
            ],
            [
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'submitted_by_user_id' => $admin->id,
                'reading_value' => 457.500,
                'validation_status' => 'valid',
                'submission_method' => 'admin_manual',
                'notes' => 'Seeded legacy operations reading.',
            ],
        );

        MeterReadingAudit::query()->updateOrCreate(
            [
                'meter_reading_id' => $reading->id,
                'change_reason' => 'Seeded baseline validation check',
            ],
            [
                'changed_by_user_id' => $admin->id,
                'old_value' => 455.200,
                'new_value' => 457.500,
            ],
        );

        $utilityService = UtilityService::query()
            ->select(['id'])
            ->where('slug', 'electricity')
            ->first();

        if ($utilityService !== null) {
            BillingRecord::query()->updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'utility_service_id' => $utilityService->id,
                    'billing_period_start' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'organization_id' => $organization->id,
                    'property_id' => $property->id,
                    'tenant_user_id' => $tenant->id,
                    'amount' => 82.35,
                    'consumption' => 457.500,
                    'rate' => 0.1800,
                    'meter_reading_start' => null,
                    'meter_reading_end' => null,
                    'billing_period_end' => now()->endOfMonth()->toDateString(),
                    'notes' => 'Seeded billing record for demo invoice.',
                ],
            );
        }

        Lease::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'property_id' => $property->id,
                'tenant_user_id' => $tenant->id,
            ],
            [
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => now()->addMonths(9)->toDateString(),
                'monthly_rent' => 750.00,
                'deposit' => 1500.00,
                'is_active' => true,
            ],
        );

        SubscriptionRenewal::query()->updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'old_expires_at' => $subscription->expires_at,
            ],
            [
                'user_id' => $admin->id,
                'method' => 'manual',
                'period' => 'monthly',
                'new_expires_at' => $subscription->expires_at?->copy()->addMonth(),
                'duration_days' => 30,
                'notes' => 'Seeded renewal history for legacy operations foundation.',
            ],
        );

        InvoiceGenerationAudit::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'period_start' => $invoice->billing_period_start,
                'period_end' => $invoice->billing_period_end,
            ],
            [
                'organization_id' => $organization->id,
                'tenant_user_id' => $tenant->id,
                'user_id' => $admin->id,
                'total_amount' => $invoice->total_amount,
                'items_count' => 2,
                'metadata' => ['seed' => 'legacy_operations_foundation'],
                'execution_time_ms' => 32.5,
                'query_count' => 8,
                'created_at' => now(),
            ],
        );
    }
}
