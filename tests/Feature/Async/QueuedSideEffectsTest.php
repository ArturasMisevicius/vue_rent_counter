<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Filament\Actions\Admin\Invoices\SendInvoiceEmailAction;
use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Filament\Pages\Reports;
use App\Jobs\GenerateAdminReportExportJob;
use App\Jobs\SendInvoiceEmailJob;
use App\Jobs\SendInvoiceReminderJob;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('queues invoice reminders instead of sending them inline', function (): void {
    Queue::fake();
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.test',
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'notification_preferences' => [
            'new_invoice_generated' => false,
            'invoice_overdue' => true,
            'tenant_submits_reading' => false,
            'subscription_expiring' => false,
        ],
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-QUEUE-001',
            'status' => InvoiceStatus::OVERDUE,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

    $queued = app(SendInvoiceReminderAction::class)->handle($invoice, $admin);

    expect($queued)->toBeTrue()
        ->and(InvoiceReminderLog::query()->count())->toBe(0)
        ->and($invoice->fresh()->last_reminder_sent_at)->toBeNull();

    Queue::assertPushed(SendInvoiceReminderJob::class, fn (SendInvoiceReminderJob $job): bool => $job->invoiceId === $invoice->id
        && $job->actorId === $admin->id);

    Notification::assertNothingSent();
});

it('queues invoice email work instead of writing delivery logs inline', function (): void {
    Queue::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.test',
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-QUEUE-002',
        ]);

    app(SendInvoiceEmailAction::class)->handle($invoice, $admin, 'tenant@example.test');

    expect(InvoiceEmailLog::query()->count())->toBe(0);

    Queue::assertPushed(SendInvoiceEmailJob::class, fn (SendInvoiceEmailJob $job): bool => $job->invoiceId === $invoice->id
        && $job->actorId === $admin->id
        && $job->recipientEmail === 'tenant@example.test');
});

it('queues report exports instead of streaming them in the interactive request', function (): void {
    Queue::fake();

    $admin = seedQueuedExportWorkspace();

    Livewire::actingAs($admin)
        ->test(Reports::class)
        ->call('exportCsv')
        ->call('exportPdf')
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateAdminReportExportJob::class, 2);
    Queue::assertPushed(GenerateAdminReportExportJob::class, fn (GenerateAdminReportExportJob $job): bool => $job->format === 'csv');
    Queue::assertPushed(GenerateAdminReportExportJob::class, fn (GenerateAdminReportExportJob $job): bool => $job->format === 'pdf');
});

function seedQueuedExportWorkspace(): User
{
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    return User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
}
