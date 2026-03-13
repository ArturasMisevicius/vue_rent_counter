<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeterReadingUpdateController Feature Tests
 * 
 * Comprehensive test suite for the single-action controller handling meter reading
 * corrections with audit trail support and automatic draft invoice recalculation.
 * 
 * Test Coverage (22 tests, 30+ assertions):
 * - Basic correction workflow with audit trail
 * - Draft invoice recalculation (automatic)
 * - Finalized invoice protection (immutability)
 * - Monotonicity validation (lower/higher bounds)
 * - Temporal validation (future dates)
 * - Change reason validation (required, min/max length)
 * - Authorization (admin, manager, tenant, superadmin, unauthenticated)
 * - Cross-tenant isolation
 * - Audit metadata capture (IP, user agent)
 * - Optional field updates (reading_date, zone)
 * - Multiple corrections (audit history)
 * - Edge cases (same value, negative values, non-numeric)
 * 
 * Requirements:
 * - 1.1: Store reading with entered_by user ID and timestamp
 * - 1.2: Validate monotonicity (reading cannot be lower than previous)
 * - 1.3: Validate temporal validity (reading date not in future)
 * - 1.4: Maintain audit trail of changes
 * - 8.1: Create audit record in meter_reading_audit table
 * - 8.2: Store old value, new value, reason, and user who made change
 * - 8.3: Recalculate affected draft invoices
 * 
 * @package Tests\Feature\Http\Controllers
 * @group controllers
 * @group meter-readings
 * @group audit
 * @group invoice-recalculation
 */
class MeterReadingUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Property $property;
    private Meter $meter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create manager user
        $this->manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        // Create property and meter
        $this->property = Property::factory()->create(['tenant_id' => 1]);
        $this->meter = Meter::factory()->create([
            'property_id' => $this->property->id,
            'type' => MeterType::ELECTRICITY,
            'tenant_id' => 1,
        ]);
    }

    /**
     * Test: Manager can successfully correct a meter reading.
     * 
     * Validates that a manager can update a meter reading with a valid
     * change reason, and the system creates an audit trail.
     * 
     * Requirements: 1.1, 1.4, 8.1, 8.2
     */
    public function test_manager_can_correct_meter_reading(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Correcting data entry error - meter was misread during initial recording',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify reading was updated
        $reading->refresh();
        $this->assertEquals('1150.00', $reading->value);

        // Verify audit trail was created
        $this->assertDatabaseHas('meter_reading_audits', [
            'meter_reading_id' => $reading->id,
            'changed_by_user_id' => $this->manager->id,
            'old_value' => '1100.00',
            'new_value' => '1150.00',
        ]);

        $audit = MeterReadingAudit::where('meter_reading_id', $reading->id)->first();
        $this->assertStringContainsString('Correcting data entry error', $audit->change_reason);
    }

    /**
     * Test: Meter reading correction triggers draft invoice recalculation.
     * 
     * Validates that when a meter reading is corrected, all affected draft
     * invoices are automatically recalculated with the new consumption values.
     * 
     * Requirements: 8.3
     */
    public function test_meter_reading_correction_recalculates_draft_invoice(): void
    {
        // Create readings
        $startReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1000.00,
            'reading_date' => now()->subMonth(),
            'tenant_id' => 1,
        ]);

        $endReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Create tenant and draft invoice
        $tenant = Tenant::factory()->create([
            'property_id' => $this->property->id,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_renter_id' => $tenant->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 20.00,
            'tenant_id' => 1,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Electricity',
            'quantity' => 100.00,
            'unit' => 'kWh',
            'unit_price' => 0.2000,
            'total' => 20.00,
            'meter_reading_snapshot' => [
                'meter_id' => $this->meter->id,
                'start_reading_id' => $startReading->id,
                'start_value' => '1000.00',
                'end_reading_id' => $endReading->id,
                'end_value' => '1100.00',
            ],
        ]);

        // Correct the end reading
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $endReading), [
                'value' => 1150.00,
                'change_reason' => 'Correcting meter reading after verification',
            ]);

        $response->assertRedirect();

        // Verify invoice was recalculated
        $invoice->refresh();
        $this->assertEquals('30.00', $invoice->total_amount); // 150 kWh * 0.20
    }

    /**
     * Test: Finalized invoices are not recalculated.
     * 
     * Validates that when a meter reading is corrected, finalized invoices
     * remain unchanged to maintain billing integrity.
     * 
     * Requirements: 8.3
     */
    public function test_finalized_invoices_are_not_recalculated(): void
    {
        // Create readings
        $startReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1000.00,
            'reading_date' => now()->subMonth(),
            'tenant_id' => 1,
        ]);

        $endReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Create tenant and finalized invoice
        $tenant = Tenant::factory()->create([
            'property_id' => $this->property->id,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_renter_id' => $tenant->id,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now()->subDay(),
            'total_amount' => 20.00,
            'tenant_id' => 1,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Electricity',
            'quantity' => 100.00,
            'unit' => 'kWh',
            'unit_price' => 0.2000,
            'total' => 20.00,
            'meter_reading_snapshot' => [
                'meter_id' => $this->meter->id,
                'start_reading_id' => $startReading->id,
                'start_value' => '1000.00',
                'end_reading_id' => $endReading->id,
                'end_value' => '1100.00',
            ],
        ]);

        $originalTotal = $invoice->total_amount;

        // Correct the end reading
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $endReading), [
                'value' => 1150.00,
                'change_reason' => 'Late correction after invoice finalization',
            ]);

        $response->assertRedirect();

        // Verify invoice was NOT recalculated
        $invoice->refresh();
        $this->assertEquals($originalTotal, $invoice->total_amount);
    }

    /**
     * Test: Monotonicity validation prevents lower values.
     * 
     * Validates that the system prevents setting a meter reading value
     * lower than the previous reading (monotonicity property).
     * 
     * Requirements: 1.2
     */
    public function test_monotonicity_validation_prevents_lower_values(): void
    {
        // Create previous reading
        $previousReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1000.00,
            'reading_date' => now()->subMonth(),
            'tenant_id' => 1,
        ]);

        // Create current reading
        $currentReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Attempt to set value below previous
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $currentReading), [
                'value' => 950.00, // Invalid: < previous (1000.00)
                'change_reason' => 'Attempting to correct reading',
            ]);

        $response->assertSessionHasErrors('value');
        
        // Verify reading was NOT updated
        $currentReading->refresh();
        $this->assertEquals('1100.00', $currentReading->value);
    }

    /**
     * Test: Monotonicity validation prevents higher values.
     * 
     * Validates that the system prevents setting a meter reading value
     * higher than the next reading (monotonicity property).
     * 
     * Requirements: 1.2
     */
    public function test_monotonicity_validation_prevents_higher_values(): void
    {
        // Create current reading
        $currentReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now()->subMonth(),
            'tenant_id' => 1,
        ]);

        // Create next reading
        $nextReading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1200.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Attempt to set value above next
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $currentReading), [
                'value' => 1250.00, // Invalid: > next (1200.00)
                'change_reason' => 'Attempting to correct reading',
            ]);

        $response->assertSessionHasErrors('value');
        
        // Verify reading was NOT updated
        $currentReading->refresh();
        $this->assertEquals('1100.00', $currentReading->value);
    }

    /**
     * Test: Temporal validation prevents future dates.
     * 
     * Validates that the system prevents setting a meter reading date
     * in the future (temporal validity property).
     * 
     * Requirements: 1.3
     */
    public function test_temporal_validation_prevents_future_dates(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Attempt to set future date
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'reading_date' => now()->addDay()->format('Y-m-d'), // Future date
                'change_reason' => 'Attempting to correct reading',
            ]);

        $response->assertSessionHasErrors('reading_date');
    }

    /**
     * Test: Change reason validation requires minimum length.
     * 
     * Validates that the system requires a meaningful change reason
     * for audit trail purposes.
     * 
     * Requirements: 8.2
     */
    public function test_change_reason_validation_requires_minimum_length(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Attempt with too short reason
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Short', // Too short (< 10 chars)
            ]);

        $response->assertSessionHasErrors('change_reason');
    }

    /**
     * Test: Change reason is required.
     * 
     * Validates that the system requires a change reason for all
     * meter reading corrections.
     * 
     * Requirements: 8.2
     */
    public function test_change_reason_is_required(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // Attempt without change reason
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
            ]);

        $response->assertSessionHasErrors('change_reason');
    }

    /**
     * Test: Unauthorized users cannot correct meter readings.
     * 
     * Validates that tenant users cannot correct meter readings
     * (authorization enforcement).
     */
    public function test_unauthorized_users_cannot_correct_meter_readings(): void
    {
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);

        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($tenant)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Attempting to correct reading',
            ]);

        $response->assertForbidden();
    }

    /**
     * Test: Cross-tenant access is prevented.
     * 
     * Validates that managers cannot correct meter readings from
     * other tenants (tenant isolation).
     */
    public function test_cross_tenant_access_is_prevented(): void
    {
        // Create reading in different tenant
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        $otherMeter = Meter::factory()->create([
            'property_id' => $otherProperty->id,
            'tenant_id' => 2,
        ]);

        $otherReading = MeterReading::factory()->create([
            'meter_id' => $otherMeter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 2,
        ]);

        // Attempt to correct reading from different tenant
        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $otherReading), [
                'value' => 1150.00,
                'change_reason' => 'Attempting to correct reading',
            ]);

        $response->assertNotFound();
    }

    /**
     * Test: Audit trail captures IP address and user agent.
     * 
     * Validates that the audit trail captures request metadata
     * for security and forensic purposes.
     * 
     * Requirements: 8.1, 8.2
     */
    public function test_audit_trail_captures_request_metadata(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->withHeader('User-Agent', 'Test Browser')
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Correcting meter reading for testing',
            ]);

        $response->assertRedirect();

        $audit = MeterReadingAudit::where('meter_reading_id', $reading->id)->first();
        $this->assertNotNull($audit->ip_address);
        $this->assertNotNull($audit->user_agent);
    }

    /**
     * Test: Admin users can correct meter readings.
     * 
     * Validates that admin users have permission to correct meter readings
     * across their tenant scope.
     */
    public function test_admin_can_correct_meter_readings(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Admin correction for data accuracy',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reading->refresh();
        $this->assertEquals('1150.00', $reading->value);
    }

    /**
     * Test: Superadmin users can correct meter readings across tenants.
     * 
     * Validates that superadmin users have cross-tenant access for
     * meter reading corrections.
     */
    public function test_superadmin_can_correct_meter_readings_across_tenants(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => 1,
        ]);

        // Create reading in different tenant
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        $otherMeter = Meter::factory()->create([
            'property_id' => $otherProperty->id,
            'tenant_id' => 2,
        ]);

        $reading = MeterReading::factory()->create([
            'meter_id' => $otherMeter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 2,
        ]);

        $response = $this->actingAs($superadmin)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'Superadmin cross-tenant correction',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * Test: Value validation requires positive number.
     * 
     * Validates that negative meter reading values are rejected.
     */
    public function test_value_validation_requires_positive_number(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => -50.00, // Invalid: negative value
                'change_reason' => 'Attempting to set negative value',
            ]);

        $response->assertSessionHasErrors('value');
    }

    /**
     * Test: Value validation requires numeric input.
     * 
     * Validates that non-numeric meter reading values are rejected.
     */
    public function test_value_validation_requires_numeric_input(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 'not-a-number', // Invalid: non-numeric
                'change_reason' => 'Attempting to set non-numeric value',
            ]);

        $response->assertSessionHasErrors('value');
    }

    /**
     * Test: Change reason validation enforces maximum length.
     * 
     * Validates that excessively long change reasons are rejected.
     * 
     * Requirements: 8.2
     */
    public function test_change_reason_validation_enforces_maximum_length(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $longReason = str_repeat('a', 501); // Exceeds max length (500)

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => $longReason,
            ]);

        $response->assertSessionHasErrors('change_reason');
    }

    /**
     * Test: Optional reading date can be updated.
     * 
     * Validates that the reading date can be optionally updated
     * during correction (within temporal constraints).
     */
    public function test_optional_reading_date_can_be_updated(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now()->subDays(5),
            'tenant_id' => 1,
        ]);

        $newDate = now()->subDays(3)->format('Y-m-d');

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'reading_date' => $newDate,
                'change_reason' => 'Correcting both value and date',
            ]);

        $response->assertRedirect();

        $reading->refresh();
        $this->assertEquals($newDate, $reading->reading_date->format('Y-m-d'));
    }

    /**
     * Test: Optional zone can be updated.
     * 
     * Validates that the zone (e.g., day/night for electricity)
     * can be optionally updated during correction.
     */
    public function test_optional_zone_can_be_updated(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'zone' => 'day',
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'zone' => 'night',
                'change_reason' => 'Correcting zone classification',
            ]);

        $response->assertRedirect();

        $reading->refresh();
        $this->assertEquals('night', $reading->zone);
    }

    /**
     * Test: Multiple corrections create multiple audit records.
     * 
     * Validates that each correction creates a separate audit record,
     * maintaining a complete history of changes.
     * 
     * Requirements: 8.1, 8.2
     */
    public function test_multiple_corrections_create_multiple_audit_records(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        // First correction
        $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1150.00,
                'change_reason' => 'First correction - initial error',
            ]);

        $reading->refresh();

        // Second correction
        $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1175.00,
                'change_reason' => 'Second correction - further adjustment',
            ]);

        // Verify two audit records exist
        $audits = MeterReadingAudit::where('meter_reading_id', $reading->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $audits);
        $this->assertEquals('1100.00', $audits[0]->old_value);
        $this->assertEquals('1150.00', $audits[0]->new_value);
        $this->assertEquals('1150.00', $audits[1]->old_value);
        $this->assertEquals('1175.00', $audits[1]->new_value);
    }

    /**
     * Test: Correction with same value still creates audit record.
     * 
     * Validates that even if the value doesn't change, an audit record
     * is created to track the correction attempt and reason.
     * 
     * Requirements: 8.1, 8.2
     */
    public function test_correction_with_same_value_creates_audit_record(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('manager.meter-readings.correct', $reading), [
                'value' => 1100.00, // Same value
                'change_reason' => 'Verification - confirming reading is correct',
            ]);

        $response->assertRedirect();

        // Verify audit record was created
        $this->assertDatabaseHas('meter_reading_audits', [
            'meter_reading_id' => $reading->id,
            'old_value' => '1100.00',
            'new_value' => '1100.00',
        ]);
    }

    /**
     * Test: Unauthenticated users are redirected to login.
     * 
     * Validates that unauthenticated requests are redirected to
     * the login page.
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'value' => 1100.00,
            'reading_date' => now(),
            'tenant_id' => 1,
        ]);

        $response = $this->put(route('manager.meter-readings.correct', $reading), [
            'value' => 1150.00,
            'change_reason' => 'Attempting unauthorized correction',
        ]);

        $response->assertRedirect(route('login'));
    }
}
