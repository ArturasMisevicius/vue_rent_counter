<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for Smoke Test Protocol (MG-04, TN-03, TN-05).
 * Creates: Draft, Overdue/Finalized, and Paid invoices.
 */
class SmokeTestInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating smoke test invoices...');

        // Get or create a property and tenant for testing
        $property = Property::first();
        
        if (! $property) {
            $this->command->info('Creating test property...');
            $property = Property::factory()->forTenantId(1)->create([
                'name' => 'Smoke Test Property',
            ]);
        }

        $tenant = Tenant::where('property_id', $property->id)->first();
        
        if (! $tenant) {
            $this->command->info('Creating test tenant...');
            $tenant = Tenant::factory()->forProperty($property)->create([
                'name' => 'Smoke Test Tenant',
                'email' => 'smoketest@example.com',
            ]);
        }

        $this->command->info("Using Property: {$property->name} (ID: {$property->id})");
        $this->command->info("Using Tenant: {$tenant->name} (ID: {$tenant->id})");

        // 1. DRAFT Invoice (for Manager testing - MG-04)
        $draftInvoice = Invoice::create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
            'invoice_number' => 'DRAFT-' . now()->format('Ymd') . '-001',
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(14),
            'total_amount' => 125.50,
            'status' => InvoiceStatus::DRAFT,
        ]);
        $this->command->info("✓ Created DRAFT Invoice: {$draftInvoice->invoice_number} (€{$draftInvoice->total_amount})");

        // 2. OVERDUE/FINALIZED Invoice (for Tenant Red Alert - TN-03)
        $overdueInvoice = Invoice::create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
            'invoice_number' => 'INV-' . now()->subMonth()->format('Ymd') . '-001',
            'billing_period_start' => now()->subMonth()->startOfMonth(),
            'billing_period_end' => now()->subMonth()->endOfMonth(),
            'due_date' => now()->subDays(10), // Past due!
            'total_amount' => 89.75,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now()->subDays(20),
        ]);
        $this->command->info("✓ Created OVERDUE Invoice: {$overdueInvoice->invoice_number} (€{$overdueInvoice->total_amount}) - Due: {$overdueInvoice->due_date->format('Y-m-d')}");

        // 3. PAID Invoice (for History - TN-05)
        $paidInvoice = Invoice::create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
            'invoice_number' => 'INV-' . now()->subMonths(2)->format('Ymd') . '-001',
            'billing_period_start' => now()->subMonths(2)->startOfMonth(),
            'billing_period_end' => now()->subMonths(2)->endOfMonth(),
            'due_date' => now()->subMonths(2)->addDays(14),
            'total_amount' => 102.30,
            'status' => InvoiceStatus::PAID,
            'finalized_at' => now()->subMonths(2)->addDays(5),
            'paid_at' => now()->subMonths(2)->addDays(10),
            'payment_reference' => 'PAY-' . Str::upper(Str::random(8)),
            'paid_amount' => 102.30,
        ]);
        $this->command->info("✓ Created PAID Invoice: {$paidInvoice->invoice_number} (€{$paidInvoice->total_amount}) - Paid: {$paidInvoice->paid_at->format('Y-m-d')}");

        $this->command->newLine();
        $this->command->info('=== SMOKE TEST DATA READY ===');
        $this->command->table(
            ['Type', 'Invoice #', 'Amount', 'Status', 'Due Date'],
            [
                ['Draft (MG-04)', $draftInvoice->invoice_number, '€' . $draftInvoice->total_amount, 'DRAFT', $draftInvoice->due_date->format('Y-m-d')],
                ['Overdue (TN-03)', $overdueInvoice->invoice_number, '€' . $overdueInvoice->total_amount, 'FINALIZED (OVERDUE)', $overdueInvoice->due_date->format('Y-m-d')],
                ['Paid (TN-05)', $paidInvoice->invoice_number, '€' . $paidInvoice->total_amount, 'PAID', $paidInvoice->due_date->format('Y-m-d')],
            ]
        );
    }
}
