<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\UserRole;
use App\Filament\Actions\Notifications\NotifyOrganizationAdmins;
use App\Filament\Actions\Notifications\NotifyOrganizationManagers;
use App\Filament\Actions\Notifications\NotifyTenant;
use App\Filament\Actions\Notifications\SendContractExpiryReminders;
use App\Filament\Actions\Notifications\SendInvoiceOverdueReminders;
use App\Filament\Actions\Notifications\SendReadingReminders;
use App\Filament\Pages\Notifications;
use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Mail\DomainNotificationMail;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\NotificationDeliveryLog;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\RentalContract;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('creates a tenant notification when an invoice is created', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace);

    $notification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_CREATED,
        subject: $invoice,
        actor: $workspace['admin'],
    );

    expect($notification)->not->toBeNull()
        ->and($notification?->type)->toBe(DomainNotificationCatalog::INVOICE_CREATED)
        ->and($notification?->organization_id)->toBe($workspace['organization']->id)
        ->and($notification?->recipient_user_id)->toBe($workspace['tenant']->id)
        ->and($notification?->read_at)->toBeNull()
        ->and($notification?->action_url)->toBe(tenantInvoiceUrl($invoice));

    expect(NotificationDeliveryLog::query()
        ->where('notification_id', $notification?->id)
        ->where('channel', 'database')
        ->where('status', 'delivered')
        ->exists())->toBeTrue();

    Mail::assertSent(DomainNotificationMail::class);
});

it('creates admin and manager notifications when a reading is submitted', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $reading = notificationReading($workspace, [
        'validation_status' => MeterReadingValidationStatus::PENDING,
        'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
    ]);

    $adminNotifications = app(NotifyOrganizationAdmins::class)->handle(
        organization: $workspace['organization'],
        type: DomainNotificationCatalog::READING_SUBMITTED,
        subject: $reading,
        actor: $workspace['tenant'],
    );
    $managerNotifications = app(NotifyOrganizationManagers::class)->handle(
        organization: $workspace['organization'],
        type: DomainNotificationCatalog::READING_SUBMITTED,
        subject: $reading,
        actor: $workspace['tenant'],
    );

    expect($adminNotifications)->toHaveCount(1)
        ->and($managerNotifications)->toHaveCount(1)
        ->and(notificationFor($workspace['admin'], DomainNotificationCatalog::READING_SUBMITTED)?->action_url)
        ->toBe(route('filament.admin.resources.meter-readings.view', ['record' => $reading], false))
        ->and(notificationFor($workspace['manager'], DomainNotificationCatalog::READING_SUBMITTED)?->organization_id)
        ->toBe($workspace['organization']->id);
});

it('creates a tenant notification when a reading is rejected', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $reading = notificationReading($workspace, [
        'validation_status' => MeterReadingValidationStatus::REJECTED,
    ]);

    $notification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::READING_REJECTED,
        subject: $reading,
        actor: $workspace['admin'],
    );

    expect($notification)->not->toBeNull()
        ->and($notification?->type)->toBe(DomainNotificationCatalog::READING_REJECTED)
        ->and($notification?->recipient_user_id)->toBe($workspace['tenant']->id)
        ->and($notification?->action_url)->toBe(route('tenant.readings.create', [], false));
});

it('creates a tenant notification when an invoice is sent', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace, [
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);

    $notification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_SENT,
        subject: $invoice,
        actor: $workspace['admin'],
    );

    expect($notification)->not->toBeNull()
        ->and($notification?->type)->toBe(DomainNotificationCatalog::INVOICE_SENT)
        ->and($notification?->action_url)->toBe(tenantInvoiceUrl($invoice));
});

it('creates overdue invoice reminders for unpaid invoices', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Mail::fake();

    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace, [
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => 125,
        'amount_paid' => 0,
        'paid_amount' => 0,
        'due_date' => today()->subDays(5)->toDateString(),
        'paid_at' => null,
    ]);

    $sent = app(SendInvoiceOverdueReminders::class)->handle($workspace['organization']);

    $notification = notificationFor($workspace['tenant'], DomainNotificationCatalog::INVOICE_OVERDUE);

    expect($sent)->toBe(1)
        ->and($notification)->not->toBeNull()
        ->and($notification?->action_url)->toBe(tenantInvoiceUrl($invoice))
        ->and($notification?->data['days'] ?? null)->toBe(5);
});

it('creates admin and manager notifications for expiring contracts', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Mail::fake();

    $workspace = notificationWorkspace();

    RentalContract::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'tenant_id' => $workspace['tenant']->id,
        'property_id' => $workspace['property']->id,
        'property_assignment_id' => $workspace['assignment']->id,
        'end_date' => today()->addDays(30)->toDateString(),
    ]);

    $sent = app(SendContractExpiryReminders::class)->handle($workspace['organization']);

    expect($sent)->toBe(2)
        ->and(notificationFor($workspace['admin'], DomainNotificationCatalog::CONTRACT_EXPIRING))->not->toBeNull()
        ->and(notificationFor($workspace['manager'], DomainNotificationCatalog::CONTRACT_EXPIRING))->not->toBeNull();
});

it('creates an admin notification when a tenant invitation is accepted', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'tenant_id' => $workspace['tenant']->id,
        'email' => $workspace['tenant']->email,
        'role' => UserRole::TENANT,
        'accepted_at' => now(),
    ]);

    app(NotifyOrganizationAdmins::class)->handle(
        organization: $workspace['organization'],
        type: DomainNotificationCatalog::TENANT_INVITATION_ACCEPTED,
        subject: $invitation,
        actor: $workspace['tenant'],
    );

    $notification = notificationFor($workspace['admin'], DomainNotificationCatalog::TENANT_INVITATION_ACCEPTED);

    expect($notification)->not->toBeNull()
        ->and($notification?->action_url)->toBe(route('filament.admin.resources.tenants.index', [], false));
});

it('creates an admin notification for service configuration errors', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'property_id' => $workspace['property']->id,
        'validation_result' => [
            'status' => 'error',
            'blocking_errors' => ['Missing tariff'],
        ],
    ]);

    app(NotifyOrganizationAdmins::class)->handle(
        organization: $workspace['organization'],
        type: DomainNotificationCatalog::SERVICE_CONFIGURATION_ERROR,
        subject: $serviceConfiguration,
        actor: $workspace['admin'],
    );

    $notification = notificationFor($workspace['admin'], DomainNotificationCatalog::SERVICE_CONFIGURATION_ERROR);

    expect($notification)->not->toBeNull()
        ->and($notification?->action_url)
        ->toBe(route('filament.admin.resources.service-configurations.edit', ['record' => $serviceConfiguration], false));
});

it('prevents a tenant from reading another tenant notification', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);
    $invoice = notificationInvoice($workspace);

    $notification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_SENT,
        subject: $invoice,
    );

    Livewire::actingAs($otherTenant)
        ->test(Notifications::class)
        ->call('openNotification', (string) $notification?->id);

    expect($notification?->fresh()?->read_at)->toBeNull();
});

it('prevents an admin from reading another organization notification', function (): void {
    $workspace = notificationWorkspace();
    $otherOrganization = Organization::factory()->create();

    $notification = DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => DomainNotificationCatalog::SERVICE_CONFIGURATION_ERROR,
        'notifiable_type' => User::class,
        'notifiable_id' => $workspace['admin']->id,
        'organization_id' => $otherOrganization->id,
        'recipient_user_id' => $workspace['admin']->id,
        'title' => 'Hidden notification',
        'message' => 'This belongs to another organization.',
        'action_url' => '/admin/service-configurations',
        'data' => [
            'business_type' => DomainNotificationCatalog::SERVICE_CONFIGURATION_ERROR,
        ],
        'read_at' => null,
        'sent_email_at' => null,
    ]);

    Livewire::actingAs($workspace['admin'])
        ->test(Notifications::class)
        ->call('openNotification', $notification->id);

    expect($notification->fresh()->read_at)->toBeNull();
});

it('does not send duplicate reading reminders', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Mail::fake();

    $workspace = notificationWorkspace();
    notificationInvoice($workspace, [
        'status' => InvoiceStatus::DRAFT,
        'automation_level' => 'reading_request',
        'approval_status' => 'waiting_for_readings',
        'due_date' => today()->addDays(3)->toDateString(),
    ]);

    $firstRun = app(SendReadingReminders::class)->handle($workspace['organization'], [3]);
    $secondRun = app(SendReadingReminders::class)->handle($workspace['organization'], [3]);

    expect($firstRun)->toBe(1)
        ->and($secondRun)->toBe(0)
        ->and($workspace['tenant']->notifications()
            ->where('type', DomainNotificationCatalog::READING_REMINDER)
            ->count())->toBe(1);
});

it('stops reminders after the required action is completed', function (): void {
    Carbon::setTestNow('2026-06-14 10:00:00');
    Mail::fake();

    $workspace = notificationWorkspace();
    notificationInvoice($workspace, [
        'status' => InvoiceStatus::DRAFT,
        'automation_level' => 'reading_request',
        'approval_status' => 'readings_submitted',
        'due_date' => today()->addDays(3)->toDateString(),
    ]);
    notificationInvoice($workspace, [
        'status' => InvoiceStatus::PAID,
        'total_amount' => 125,
        'amount_paid' => 125,
        'paid_amount' => 125,
        'due_date' => today()->subDays(5)->toDateString(),
        'paid_at' => now(),
    ]);

    expect(app(SendReadingReminders::class)->handle($workspace['organization'], [3]))->toBe(0)
        ->and(app(SendInvoiceOverdueReminders::class)->handle($workspace['organization']))->toBe(0)
        ->and($workspace['tenant']->notifications()->count())->toBe(0);
});

it('logs email failures without breaking notification creation', function (): void {
    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace);

    Mail::swap(new class
    {
        public function to(string $email): self
        {
            return $this;
        }

        public function send(DomainNotificationMail $mail): void
        {
            throw new RuntimeException('SMTP connection failed.');
        }
    });

    $notification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_SENT,
        subject: $invoice,
    );

    expect($notification)->not->toBeNull()
        ->and($notification?->sent_email_at)->toBeNull()
        ->and(NotificationDeliveryLog::query()
            ->where('notification_id', $notification?->id)
            ->where('channel', 'mail')
            ->where('status', 'failed')
            ->whereNotNull('failed_at')
            ->where('error_message', 'SMTP connection failed.')
            ->exists())->toBeTrue();
});

it('marks notifications as read from the notification center', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace);
    $notification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_SENT,
        subject: $invoice,
    );

    Livewire::actingAs($workspace['tenant'])
        ->test(Notifications::class)
        ->call('openNotification', (string) $notification?->id)
        ->assertRedirect(tenantInvoiceUrl($invoice));

    expect($notification?->fresh()?->read_at)->not->toBeNull();
});

it('stores action urls that point to the relevant tenant pages', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace);

    $readingNotification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::READING_REQUIRED,
        subject: $invoice,
        data: ['send_email' => false],
    );
    $invoiceNotification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_SENT,
        subject: $invoice,
        data: ['send_email' => false],
    );

    expect($readingNotification?->action_url)
        ->toBe(route('tenant.readings.create', ['invoice' => $invoice->id], false))
        ->and($invoiceNotification?->action_url)
        ->toBe(tenantInvoiceUrl($invoice));
});

it('filters the notification center by type and status', function (): void {
    Mail::fake();

    $workspace = notificationWorkspace();
    $invoice = notificationInvoice($workspace);
    $readNotification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::READING_REQUIRED,
        subject: $invoice,
        data: ['send_email' => false],
    );
    $unreadNotification = app(NotifyTenant::class)->handle(
        tenant: $workspace['tenant'],
        type: DomainNotificationCatalog::INVOICE_SENT,
        subject: $invoice,
        data: ['send_email' => false],
    );

    $readNotification?->markAsRead();

    Livewire::actingAs($workspace['tenant'])
        ->test(Notifications::class)
        ->set('typeFilter', DomainNotificationCatalog::INVOICE_SENT)
        ->assertSee($unreadNotification?->title)
        ->assertDontSee($readNotification?->title)
        ->set('statusFilter', 'read')
        ->set('typeFilter', null)
        ->assertSee($readNotification?->title)
        ->assertDontSee($unreadNotification?->title);
});

/**
 * @return array{organization: Organization, admin: User, manager: User, tenant: User, property: Property, assignment: PropertyAssignment}
 */
function notificationWorkspace(): array
{
    $workspace = createOrgWithAdmin();
    $tenantSetup = createTenantInOrg($workspace['admin']);
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    return [
        'organization' => $workspace['organization']->fresh(),
        'admin' => $workspace['admin']->fresh(),
        'manager' => $manager->fresh(),
        'tenant' => $tenantSetup['tenant']->fresh(),
        'property' => $tenantSetup['property']->fresh(),
        'assignment' => $tenantSetup['assignment']->fresh(),
    ];
}

/**
 * @param  array{organization: Organization, tenant: User, property: Property}  $workspace
 * @param  array<string, mixed>  $overrides
 */
function notificationInvoice(array $workspace, array $overrides = []): Invoice
{
    return Invoice::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'property_id' => $workspace['property']->id,
        'tenant_user_id' => $workspace['tenant']->id,
        'status' => InvoiceStatus::DRAFT,
        'automation_level' => 'manual',
        'approval_status' => 'pending',
        'approval_metadata' => [],
        'snapshot_data' => [],
        ...$overrides,
    ]);
}

/**
 * @param  array{organization: Organization, tenant: User, property: Property}  $workspace
 * @param  array<string, mixed>  $overrides
 */
function notificationReading(array $workspace, array $overrides = []): MeterReading
{
    $meter = Meter::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'property_id' => $workspace['property']->id,
    ]);

    return MeterReading::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'property_id' => $workspace['property']->id,
        'meter_id' => $meter->id,
        'submitted_by_user_id' => $workspace['tenant']->id,
        ...$overrides,
    ]);
}

function notificationFor(User $user, string $type): ?DatabaseNotification
{
    return $user->notifications()
        ->where('type', $type)
        ->latest()
        ->first();
}

function tenantInvoiceUrl(Invoice $invoice): string
{
    return route('tenant.invoices.index', [], false).'#tenant-invoice-'.$invoice->id;
}
