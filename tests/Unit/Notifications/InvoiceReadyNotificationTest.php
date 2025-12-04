<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvoiceReadyNotification;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * InvoiceReadyNotification Tests (Phase 9)
 *
 * Tests email notification functionality for invoice delivery:
 * - Mail channel configuration
 * - Email subject and content
 * - PDF attachment
 * - Recipient handling
 *
 * @group notifications
 * @group email
 * @group phase-9
 */
class InvoiceReadyNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenantUser;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant user with property
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->tenantUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Create invoice
        $this->invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'invoice_number' => 'INV-2024-001',
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'total_amount' => 250.50,
            'status' => InvoiceStatus::FINALIZED,
        ]);
    }

    // ========================================
    // NOTIFICATION DELIVERY TESTS
    // ========================================

    /** @test */
    public function it_sends_mail_notification(): void
    {
        // Arrange
        Notification::fake();

        // Act: Send notification
        $this->tenantUser->notify(new InvoiceReadyNotification($this->invoice));

        // Assert: Notification was sent
        Notification::assertSentTo(
            $this->tenantUser,
            InvoiceReadyNotification::class
        );
    }

    /** @test */
    public function it_uses_mail_channel(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Get notification channels
        $channels = $notification->via($this->tenantUser);

        // Assert: Uses mail channel
        $this->assertContains('mail', $channels);
        $this->assertCount(1, $channels);
    }

    // ========================================
    // EMAIL CONTENT TESTS
    // ========================================

    /** @test */
    public function email_has_correct_subject(): void
    {
        // Arrange
        Notification::fake();

        // Act
        $this->tenantUser->notify(new InvoiceReadyNotification($this->invoice));

        // Assert: Check notification was sent with correct invoice
        Notification::assertSentTo(
            $this->tenantUser,
            InvoiceReadyNotification::class,
            function ($notification, $channels) {
                $mailMessage = $notification->toMail($this->tenantUser);

                return $mailMessage->subject === 'Invoice INV-2024-001 is ready';
            }
        );
    }

    /** @test */
    public function email_contains_total_amount(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Email contains total amount
        $this->assertContains(
            '**Total Amount:** €250.50',
            $mailMessage->introLines
        );
    }

    /** @test */
    public function email_contains_billing_period(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Email contains billing period
        $this->assertContains(
            '**Billing Period:** 2024-01-01 to 2024-01-31',
            $mailMessage->introLines
        );
    }

    /** @test */
    public function email_contains_invoice_number(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Email mentions invoice number in content
        $found = false;
        foreach ($mailMessage->introLines as $line) {
            if (str_contains($line, 'INV-2024-001')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Email should contain invoice number in content');
    }

    /** @test */
    public function email_greeting_includes_tenant_name(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Greeting includes tenant name
        $this->assertEquals('Hello John Doe!', $mailMessage->greeting);
    }

    // ========================================
    // PDF ATTACHMENT TESTS
    // ========================================

    /** @test */
    public function email_has_pdf_attachment(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Email has attachments
        $this->assertNotEmpty($mailMessage->rawAttachments, 'Email should have attachments');
        $this->assertCount(1, $mailMessage->rawAttachments, 'Email should have exactly one attachment');
    }

    /** @test */
    public function attachment_is_pdf_with_correct_mime_type(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Attachment is PDF
        $attachment = $mailMessage->rawAttachments[0];
        $this->assertEquals('application/pdf', $attachment['options']['mime']);
    }

    /** @test */
    public function attachment_has_correct_filename(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Attachment has correct filename
        $attachment = $mailMessage->rawAttachments[0];
        $this->assertEquals('invoice_INV-2024-001.pdf', $attachment['name']);
    }

    /** @test */
    public function attachment_contains_pdf_data(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Attachment has PDF content (starts with PDF header)
        $attachment = $mailMessage->rawAttachments[0];
        $this->assertStringStartsWith('%PDF', $attachment['data']);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    /** @test */
    public function notification_handles_invoice_without_invoice_number(): void
    {
        // Arrange: Invoice without invoice_number
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'invoice_number' => null,
            'total_amount' => 100.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        $notification = new InvoiceReadyNotification($invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Uses fallback invoice number format
        $this->assertEquals("Invoice INV-{$invoice->id} is ready", $mailMessage->subject);
    }

    /** @test */
    public function notification_sanitizes_special_characters_in_filename(): void
    {
        // Arrange: Invoice with special characters in invoice_number
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'invoice_number' => 'INV/2024#TEST*001',
            'total_amount' => 100.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        $notification = new InvoiceReadyNotification($invoice);

        // Act: Generate mail message
        $mailMessage = $notification->toMail($this->tenantUser);

        // Assert: Special characters are replaced in filename
        $attachment = $mailMessage->rawAttachments[0];
        $this->assertEquals('invoice_INV_2024_TEST_001.pdf', $attachment['name']);
        $this->assertStringNotContainsString('/', $attachment['name']);
        $this->assertStringNotContainsString('#', $attachment['name']);
        $this->assertStringNotContainsString('*', $attachment['name']);
    }

    /** @test */
    public function notification_can_be_queued(): void
    {
        // Assert: Notification implements ShouldQueue
        $notification = new InvoiceReadyNotification($this->invoice);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
    }

    /** @test */
    public function to_array_returns_invoice_data(): void
    {
        // Arrange
        $notification = new InvoiceReadyNotification($this->invoice);

        // Act: Get array representation
        $array = $notification->toArray($this->tenantUser);

        // Assert: Contains invoice data
        $this->assertArrayHasKey('invoice_id', $array);
        $this->assertArrayHasKey('invoice_number', $array);
        $this->assertArrayHasKey('total_amount', $array);
        $this->assertEquals($this->invoice->id, $array['invoice_id']);
        $this->assertEquals('INV-2024-001', $array['invoice_number']);
        $this->assertEquals(250.50, $array['total_amount']);
    }
}
