<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InvoiceSnapshotService;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Property;
use App\ValueObjects\BillingOptions;
use App\Enums\BillingSchedule;
use App\Enums\ApprovalStatus;
use App\Enums\AutomationLevel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnhancedInvoiceSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceSnapshotService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoiceSnapshotService::class);
    }

    public function test_billing_options_automation_level_detection()
    {
        // Test fully automated
        $options = BillingOptions::create(
            autoApprove: true,
            requireApproval: false
        );
        $this->assertEquals(AutomationLevel::FULLY_AUTOMATED, $options->getAutomationLevel());

        // Test semi-automated
        $options = BillingOptions::create(
            autoCollectReadings: true,
            processSharedServices: false
        );
        $this->assertEquals(AutomationLevel::SEMI_AUTOMATED, $options->getAutomationLevel());

        // Test approval required
        $options = BillingOptions::create(
            requireApproval: true
        );
        $this->assertEquals(AutomationLevel::APPROVAL_REQUIRED, $options->getAutomationLevel());

        // Test manual
        $options = BillingOptions::create();
        $this->assertEquals(AutomationLevel::MANUAL, $options->getAutomationLevel());
    }

    public function test_billing_options_auto_approval_threshold()
    {
        $options = BillingOptions::create(
            approvalThreshold: 200.00
        );
        
        $this->assertEquals(200.00, $options->getAutoApprovalThreshold());
    }

    public function test_billing_options_requires_approval_workflow()
    {
        $options = BillingOptions::create(
            requireApproval: true
        );
        
        $this->assertTrue($options->requiresApprovalWorkflow());
        
        $options = BillingOptions::create(
            requireApproval: false
        );
        
        $this->assertFalse($options->requiresApprovalWorkflow());
    }

    public function test_billing_options_validation()
    {
        // Valid options
        $options = BillingOptions::create();
        $this->assertTrue($options->isValid());
        $this->assertEmpty($options->validate());

        // Invalid date range
        $options = BillingOptions::create(
            startDate: Carbon::now()->addDay(),
            endDate: Carbon::now()
        );
        $this->assertFalse($options->isValid());
        $errors = $options->validate();
        $this->assertContains('Start date must be before end date', $errors);

        // Invalid approval configuration
        $options = BillingOptions::create(
            autoApprove: true,
            requireApproval: true
        );
        $this->assertFalse($options->isValid());
        $errors = $options->validate();
        $this->assertContains('Cannot have both auto-approve and require approval enabled', $errors);
    }

    public function test_approval_status_enum_methods()
    {
        $this->assertTrue(ApprovalStatus::APPROVED->isApproved());
        $this->assertTrue(ApprovalStatus::AUTO_APPROVED->isApproved());
        $this->assertFalse(ApprovalStatus::PENDING->isApproved());

        $this->assertTrue(ApprovalStatus::PENDING->isPending());
        $this->assertTrue(ApprovalStatus::REQUIRES_REVIEW->isPending());
        $this->assertFalse(ApprovalStatus::APPROVED->isPending());

        $this->assertTrue(ApprovalStatus::REJECTED->isRejected());
        $this->assertFalse(ApprovalStatus::APPROVED->isRejected());
    }

    public function test_automation_level_enum_methods()
    {
        $this->assertTrue(AutomationLevel::MANUAL->requiresHumanIntervention());
        $this->assertTrue(AutomationLevel::APPROVAL_REQUIRED->requiresHumanIntervention());
        $this->assertFalse(AutomationLevel::FULLY_AUTOMATED->requiresHumanIntervention());

        $this->assertTrue(AutomationLevel::FULLY_AUTOMATED->isFullyAutomated());
        $this->assertFalse(AutomationLevel::MANUAL->isFullyAutomated());

        $this->assertTrue(AutomationLevel::SEMI_AUTOMATED->isSemiAutomated());
        $this->assertFalse(AutomationLevel::MANUAL->isSemiAutomated());
    }

    public function test_invoice_approval_methods()
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $renter = Tenant::factory()->create(['tenant_id' => $tenant->id]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'tenant_renter_id' => $renter->id,
            'approval_status' => ApprovalStatus::PENDING,
            'automation_level' => AutomationLevel::MANUAL,
        ]);

        // Test status checks
        $this->assertTrue($invoice->isPendingApproval());
        $this->assertFalse($invoice->isApproved());
        $this->assertFalse($invoice->isRejected());
        $this->assertTrue($invoice->requiresApproval());

        // Test approval
        $invoice->approve();
        $this->assertTrue($invoice->isApproved());
        $this->assertFalse($invoice->isPendingApproval());
        $this->assertEquals(ApprovalStatus::APPROVED, $invoice->approval_status);
        $this->assertNotNull($invoice->approved_at);

        // Test rejection
        $invoice->approval_status = ApprovalStatus::PENDING;
        $invoice->save();
        $invoice->reject(reason: 'Test rejection');
        $this->assertTrue($invoice->isRejected());
        $this->assertEquals(ApprovalStatus::REJECTED, $invoice->approval_status);
        $this->assertArrayHasKey('rejection_reason', $invoice->approval_metadata);

        // Test auto-approval
        $invoice->approval_status = ApprovalStatus::PENDING;
        $invoice->save();
        $invoice->autoApprove();
        $this->assertTrue($invoice->isApproved());
        $this->assertEquals(ApprovalStatus::AUTO_APPROVED, $invoice->approval_status);
    }

    public function test_invoice_automation_level_methods()
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $renter = Tenant::factory()->create(['tenant_id' => $tenant->id]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'tenant_renter_id' => $renter->id,
            'automation_level' => AutomationLevel::FULLY_AUTOMATED,
        ]);

        $this->assertTrue($invoice->isFullyAutomated());
        $this->assertFalse($invoice->isSemiAutomated());
        $this->assertFalse($invoice->isManual());

        $invoice->automation_level = AutomationLevel::SEMI_AUTOMATED;
        $invoice->save();

        $this->assertFalse($invoice->isFullyAutomated());
        $this->assertTrue($invoice->isSemiAutomated());
        $this->assertFalse($invoice->isManual());

        $invoice->automation_level = AutomationLevel::MANUAL;
        $invoice->save();

        $this->assertFalse($invoice->isFullyAutomated());
        $this->assertFalse($invoice->isSemiAutomated());
        $this->assertTrue($invoice->isManual());
    }
}