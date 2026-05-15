<?php

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Filament\Resources\Attachments\AttachmentResource;
use App\Filament\Resources\Attachments\Pages\CreateAttachment;
use App\Filament\Resources\Attachments\Pages\ListAttachments;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\CommentReactions\CommentReactionResource;
use App\Filament\Resources\CommentReactions\Pages\CreateCommentReaction;
use App\Filament\Resources\CommentReactions\Pages\ListCommentReactions;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\InvoiceEmailLogs\InvoiceEmailLogResource;
use App\Filament\Resources\InvoiceEmailLogs\Pages\CreateInvoiceEmailLog;
use App\Filament\Resources\InvoiceEmailLogs\Pages\ListInvoiceEmailLogs;
use App\Filament\Resources\InvoiceItems\InvoiceItemResource;
use App\Filament\Resources\InvoiceItems\Pages\CreateInvoiceItem;
use App\Filament\Resources\InvoiceItems\Pages\ListInvoiceItems;
use App\Filament\Resources\InvoicePayments\InvoicePaymentResource;
use App\Filament\Resources\InvoicePayments\Pages\CreateInvoicePayment;
use App\Filament\Resources\InvoiceReminderLogs\InvoiceReminderLogResource;
use App\Filament\Resources\InvoiceReminderLogs\Pages\CreateInvoiceReminderLog;
use App\Filament\Resources\InvoiceReminderLogs\Pages\ListInvoiceReminderLogs;
use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\OrganizationUsers\Pages\CreateOrganizationUser;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\PropertyAssignments\Pages\CreatePropertyAssignment;
use App\Filament\Resources\PropertyAssignments\PropertyAssignmentResource;
use App\Filament\Resources\SubscriptionPayments\Pages\CreateSubscriptionPayment;
use App\Filament\Resources\SubscriptionPayments\SubscriptionPaymentResource;
use App\Filament\Resources\SubscriptionRenewals\Pages\CreateSubscriptionRenewal;
use App\Filament\Resources\SubscriptionRenewals\Pages\ListSubscriptionRenewals;
use App\Filament\Resources\SubscriptionRenewals\SubscriptionRenewalResource;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\TaskAssignments\Pages\CreateTaskAssignment;
use App\Filament\Resources\TaskAssignments\Pages\ListTaskAssignments;
use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Filament\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Filament\Resources\TimeEntries\TimeEntryResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\UtilityServices\Pages\ListUtilityServices;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Attachment;
use App\Models\Building;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoiceItem;
use App\Models\InvoiceReminderLog;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('localizes superadmin relation resource page titles', function (string $resourceClass, string $translationKey): void {
    app()->setLocale('lt');

    expect($resourceClass::getPluralModelLabel())->toBe(__($translationKey));
})->with([
    [OrganizationUserResource::class, 'superadmin.relation_resources.organization_users.plural'],
    [TaskResource::class, 'superadmin.relation_resources.tasks.plural'],
    [TaskAssignmentResource::class, 'superadmin.relation_resources.task_assignments.plural'],
    [TimeEntryResource::class, 'superadmin.relation_resources.time_entries.plural'],
    [TagResource::class, 'superadmin.relation_resources.tags.plural'],
    [CommentResource::class, 'superadmin.comments_resource.plural'],
    [CommentReactionResource::class, 'superadmin.relation_resources.comment_reactions.plural'],
    [AttachmentResource::class, 'superadmin.relation_resources.attachments.plural'],
    [InvoiceItemResource::class, 'superadmin.relation_resources.invoice_items.plural'],
    [InvoicePaymentResource::class, 'superadmin.relation_resources.invoice_payments.plural'],
    [InvoiceEmailLogResource::class, 'superadmin.relation_resources.invoice_email_logs.plural'],
    [InvoiceReminderLogResource::class, 'superadmin.relation_resources.invoice_reminder_logs.plural'],
    [PropertyAssignmentResource::class, 'superadmin.relation_resources.property_assignments.plural'],
    [SubscriptionPaymentResource::class, 'superadmin.relation_resources.subscription_payments.plural'],
    [SubscriptionRenewalResource::class, 'superadmin.relation_resources.subscription_renewals.plural'],
]);

it('localizes fixed superadmin table values in Lithuanian', function (): void {
    app()->setLocale('lt');

    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'Demo Building 01-01',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Demo Unit 01-01',
    ]);
    $generatedProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 101',
        'unit_number' => '101',
        'type' => PropertyType::APARTMENT,
    ]);
    $project = Project::factory()->for($organization)->create([
        'building_id' => $building->id,
        'property_id' => $property->id,
        'name' => 'Legacy Collaboration Demo Project',
        'description' => 'Imported collaboration foundation demo project.',
        'status' => 'draft',
        'priority' => 'critical',
        'type' => 'maintenance',
    ]);
    $task = Task::factory()->for($organization)->for($project)->create([
        'title' => 'Demo Task 01-01',
        'description' => 'Inspect shared systems.',
        'status' => 'in_progress',
        'priority' => 'high',
        'due_date' => '2026-04-02',
    ]);
    $assignment = TaskAssignment::factory()->for($task)->create([
        'role' => 'assignee',
    ]);
    $timeEntry = TimeEntry::factory()->for($task)->for($assignment, 'assignment')->create([
        'description' => 'Seeded demo time entry for imported collaboration task.',
    ]);
    $systemTag = Tag::factory()->for($organization)->create([
        'name' => 'Legacy Foundation',
        'slug' => 'legacy-foundation',
        'type' => 'project',
        'is_system' => true,
    ]);
    $comment = Comment::factory()->for($organization)->create([
        'commentable_type' => Project::class,
        'commentable_id' => $project->id,
        'body' => 'Legacy collaboration layer imported successfully.',
    ]);
    $reaction = CommentReaction::factory()->for($comment)->create([
        'type' => 'like',
    ]);
    $attachment = Attachment::factory()->for($organization)->create([
        'attachable_type' => Project::class,
        'attachable_id' => $project->id,
        'description' => 'Seeded demo collaboration attachment.',
    ]);
    $invoice = Invoice::factory()->for($organization)->create();
    $invoiceItem = InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Shared services fee',
        'unit' => 'month',
    ]);
    $emailLog = InvoiceEmailLog::factory()->for($invoice)->for($organization)->create([
        'status' => 'sent',
    ]);
    $reminderLog = InvoiceReminderLog::factory()->for($invoice)->for($organization)->create([
        'channel' => 'email',
    ]);
    $subscription = Subscription::factory()->for($organization)->create();
    $renewal = SubscriptionRenewal::factory()->for($subscription)->create([
        'method' => 'manual',
        'period' => 'monthly',
        'notes' => 'Seeded renewal history for legacy operations foundation.',
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Org 01 Electricity',
        'slug' => 'org-01-electricity',
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'description' => 'Electricity utility for tenant consumption billing.',
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListTasks::class)
        ->assertSeeText('Senos bendradarbiavimo sistemos demonstracinis projektas')
        ->assertSeeText('Demonstracinė užduotis 01-01')
        ->assertDontSeeText('Legacy Collaboration Demo Project')
        ->assertDontSeeText('Demo Task 01-01')
        ->assertTableColumnStateSet('status', 'Vykdoma', $task)
        ->assertTableColumnStateSet('priority', 'Aukštas', $task)
        ->assertSee('2026 m. balandžio 2 d.');

    Livewire::test(ListProjects::class)
        ->assertSeeText('Senos bendradarbiavimo sistemos demonstracinis projektas')
        ->assertSeeText('Demonstracinis pastatas 01-01')
        ->assertSeeText('Demonstracinė patalpa 01-01')
        ->assertDontSeeText('Legacy Collaboration Demo Project')
        ->assertDontSeeText('Demo Building 01-01')
        ->assertDontSeeText('Demo Unit 01-01')
        ->assertSeeText('Juodraštis')
        ->assertSeeText('Kritinis')
        ->assertSeeText('Priežiūra');

    Livewire::test(ListTaskAssignments::class)
        ->assertTableColumnStateSet('role', 'Vykdytojas', $assignment);

    Livewire::test(ListTimeEntries::class)
        ->assertTableColumnStateSet('assignment.role', 'Vykdytojas', $timeEntry);

    Livewire::test(ListTags::class)
        ->assertTableColumnStateSet('name', 'Senos sistemos pagrindas', $systemTag)
        ->assertTableColumnStateSet('type', 'Projektas', $systemTag);

    Livewire::test(ListComments::class)
        ->assertTableColumnStateSet('commentable_type', 'Projektas', $comment)
        ->assertTableColumnStateSet('body', 'Senas bendradarbiavimo sluoksnis importuotas sėkmingai.', $comment)
        ->assertDontSeeText('Legacy collaboration layer imported successfully.');

    Livewire::test(ListCommentReactions::class)
        ->assertTableColumnStateSet('type', 'Patinka', $reaction);

    Livewire::test(ListAttachments::class)
        ->assertTableColumnStateSet('attachable_type', 'Projektas', $attachment);

    Livewire::test(ListInvoiceItems::class)
        ->assertTableColumnStateSet('description', __('tenant.invoice_line_items.shared_services_fee'), $invoiceItem)
        ->assertTableColumnStateSet('unit', __('tenant.invoice_units.month'), $invoiceItem);

    Livewire::test(ListInvoiceEmailLogs::class)
        ->assertTableColumnStateSet('status', 'Išsiųsta', $emailLog);

    Livewire::test(ListInvoiceReminderLogs::class)
        ->assertTableColumnStateSet('channel', 'El. paštas', $reminderLog);

    Livewire::test(ListSubscriptionRenewals::class)
        ->assertTableColumnStateSet('method', 'Rankinis', $renewal)
        ->assertTableColumnStateSet('period', 'Mėnesinis', $renewal);

    Livewire::test(ListUtilityServices::class)
        ->assertTableColumnStateSet('name', 'Organizacija 01: Elektra', $utilityService)
        ->assertDontSeeText('Org 01 Electricity');

    $localizer = app(DatabaseContentLocalizer::class);

    expect($localizer->attachmentDescription($attachment->description))->toBe('Importuotas demonstracinis bendradarbiavimo priedas.')
        ->and($localizer->subscriptionRenewalNotes($renewal->notes))->toBe('Importuota senos operacijų sistemos prenumeratos atnaujinimo istorija.')
        ->and($localizer->timeEntryDescription($timeEntry->description))->toBe('Importuotas demonstracinis laiko įrašas bendradarbiavimo užduočiai.')
        ->and($localizer->utilityServiceDescription($utilityService->description))->toBe('Elektros paslauga nuomininkų suvartojimo atsiskaitymui.')
        ->and($localizer->tagDescription('Imported from the legacy collaboration foundation.'))->toBe('Importuota iš seno bendradarbiavimo pagrindo.')
        ->and($localizer->meterReadingChangeReason('Seeded baseline validation check'))->toBe('Importuota bazinė patikros priežastis')
        ->and($localizer->systemConfigurationDescription('Default billing currency for platform-level operations.'))->toBe('Numatytoji atsiskaitymo valiuta platformos lygio operacijoms.');

    expect($subscription->propertiesUsedSummary())->toBe('0 iš 10');
    expect($generatedProperty->displayName())->toBe('Butas 101');
});

it('localizes superadmin relation create form labels in Lithuanian', function (string $pageClass, array $fieldLabels): void {
    app()->setLocale('lt');

    $this->actingAs(User::factory()->superadmin()->create());

    $component = Livewire::test($pageClass);

    foreach ($fieldLabels as $field => $translationKey) {
        $component->assertFormFieldExists(
            $field,
            fn ($formField): bool => (string) $formField->getLabel() === __($translationKey),
        );
    }
})->with([
    'organization users' => [CreateOrganizationUser::class, [
        'organization_id' => 'superadmin.relation_resources.organization_users.fields.organization',
        'user_id' => 'superadmin.relation_resources.organization_users.fields.user',
        'role' => 'superadmin.relation_resources.organization_users.fields.role',
        'is_active' => 'superadmin.relation_resources.organization_users.fields.is_active',
    ]],
    'tasks' => [CreateTask::class, [
        'organization_id' => 'superadmin.organizations.singular',
        'project_id' => 'superadmin.relation_resources.tasks.fields.project',
        'title' => 'superadmin.relation_resources.tasks.fields.title',
        'description' => 'superadmin.relation_resources.tasks.fields.description',
        'status' => 'superadmin.relation_resources.tasks.fields.status',
        'priority' => 'superadmin.relation_resources.tasks.fields.priority',
        'completed_at' => 'superadmin.relation_resources.tasks.fields.completed_at',
    ]],
    'task assignments' => [CreateTaskAssignment::class, [
        'task_id' => 'superadmin.relation_resources.task_assignments.fields.task',
        'user_id' => 'superadmin.relation_resources.task_assignments.fields.user',
        'role' => 'superadmin.relation_resources.task_assignments.fields.role',
        'completed_at' => 'superadmin.relation_resources.task_assignments.fields.completed_at',
    ]],
    'time entries' => [CreateTimeEntry::class, [
        'user_id' => 'superadmin.relation_resources.time_entries.fields.user',
        'task_id' => 'superadmin.relation_resources.time_entries.fields.task',
        'assignment_id' => 'superadmin.relation_resources.time_entries.fields.assignment',
        'description' => 'superadmin.relation_resources.time_entries.fields.description',
    ]],
    'tags' => [CreateTag::class, [
        'organization_id' => 'superadmin.relation_resources.tags.fields.organization',
        'name' => 'superadmin.relation_resources.tags.fields.name',
        'description' => 'superadmin.relation_resources.tags.fields.description',
        'type' => 'superadmin.relation_resources.tags.fields.type',
    ]],
    'attachments' => [CreateAttachment::class, [
        'organization_id' => 'superadmin.relation_resources.attachments.fields.organization',
        'attachable_type' => 'superadmin.relation_resources.attachments.fields.attachable_type',
        'uploaded_by_user_id' => 'superadmin.relation_resources.attachments.fields.uploader',
        'metadata' => 'superadmin.relation_resources.attachments.fields.metadata',
    ]],
    'comment reactions' => [CreateCommentReaction::class, [
        'comment_id' => 'superadmin.relation_resources.comment_reactions.fields.comment',
        'user_id' => 'superadmin.relation_resources.comment_reactions.fields.user',
        'type' => 'superadmin.relation_resources.comment_reactions.fields.type',
    ]],
    'invoice items' => [CreateInvoiceItem::class, [
        'invoice_id' => 'superadmin.relation_resources.invoice_items.fields.invoice',
        'description' => 'superadmin.relation_resources.invoice_items.fields.description',
        'meter_reading_snapshot' => 'superadmin.relation_resources.invoice_items.fields.meter_reading_snapshot',
    ]],
    'invoice payments' => [CreateInvoicePayment::class, [
        'invoice_id' => 'superadmin.relation_resources.invoice_payments.fields.invoice',
        'organization_id' => 'superadmin.relation_resources.invoice_payments.fields.organization',
        'method' => 'superadmin.relation_resources.invoice_payments.fields.method',
        'notes' => 'superadmin.relation_resources.invoice_payments.fields.notes',
    ]],
    'invoice email logs' => [CreateInvoiceEmailLog::class, [
        'invoice_id' => 'superadmin.relation_resources.invoice_email_logs.fields.invoice',
        'organization_id' => 'superadmin.relation_resources.invoice_email_logs.fields.organization',
        'recipient_email' => 'superadmin.relation_resources.invoice_email_logs.fields.recipient_email',
        'status' => 'superadmin.relation_resources.invoice_email_logs.fields.status',
    ]],
    'invoice reminder logs' => [CreateInvoiceReminderLog::class, [
        'invoice_id' => 'superadmin.relation_resources.invoice_reminder_logs.fields.invoice',
        'organization_id' => 'superadmin.relation_resources.invoice_reminder_logs.fields.organization',
        'recipient_email' => 'superadmin.relation_resources.invoice_reminder_logs.fields.recipient_email',
        'channel' => 'superadmin.relation_resources.invoice_reminder_logs.fields.channel',
    ]],
    'property assignments' => [CreatePropertyAssignment::class, [
        'organization_id' => 'superadmin.relation_resources.property_assignments.fields.organization',
        'property_id' => 'superadmin.relation_resources.property_assignments.fields.property',
        'tenant_user_id' => 'superadmin.relation_resources.property_assignments.fields.tenant',
    ]],
    'subscription payments' => [CreateSubscriptionPayment::class, [
        'organization_id' => 'superadmin.relation_resources.subscription_payments.fields.organization',
        'subscription_id' => 'superadmin.relation_resources.subscription_payments.fields.subscription',
        'duration' => 'superadmin.relation_resources.subscription_payments.fields.duration',
    ]],
    'subscription renewals' => [CreateSubscriptionRenewal::class, [
        'subscription_id' => 'superadmin.relation_resources.subscription_renewals.fields.subscription',
        'user_id' => 'superadmin.relation_resources.subscription_renewals.fields.user',
        'method' => 'superadmin.relation_resources.subscription_renewals.fields.method',
        'period' => 'superadmin.relation_resources.subscription_renewals.fields.period',
        'notes' => 'superadmin.relation_resources.subscription_renewals.fields.notes',
    ]],
]);

it('resolves localized Filament table chrome labels', function (): void {
    app()->setLocale('lt');

    Lang::addLines([
        'table.filters.actions.reset.label' => 'Išvalyti filtrus',
    ], 'lt', 'filament-tables');

    expect(__('filament-tables::table.fields.search.label'))->toBe('Paieška')
        ->and(trans_choice('filament-tables::table.columns.actions.label', 2))->toBe('Veiksmai')
        ->and(__('filament-tables::table.column_manager.actions.reset.label'))->toBe('Atstatyti')
        ->and(__('filament-tables::table.column_manager.actions.apply.label'))->toBe('Taikyti stulpelius');
});

it('uses neutral localized Filament create chrome in Lithuanian', function (): void {
    app()->setLocale('lt');

    expect(__('filament-actions::create.single.label', ['label' => 'Užduotis']))->toBe('Sukurti įrašą')
        ->and(__('filament-actions::create.single.modal.heading', ['label' => 'Užduotis']))->toBe('Sukurti įrašą')
        ->and(__('filament-panels::resources/pages/create-record.title', ['label' => 'Užduotis']))->toBe('Sukurti įrašą');
});

it('keeps localized Filament resource chrome in sentence case', function (): void {
    app()->setLocale('lt');

    expect(AuditLogResource::getTitleCasePluralModelLabel())->toBe('Audito įrašai')
        ->and(__('filament-panels::resources/pages/view-record.title', ['label' => 'Užduotis']))->toBe('Peržiūrėti įrašą')
        ->and(__('filament-panels::resources/pages/edit-record.title', ['label' => 'Užduotis']))->toBe('Redaguoti įrašą');
});

it('localizes superadmin user dossier field names and related enum values', function (): void {
    app()->setLocale('lt');

    $organization = Organization::factory()->create([
        'status' => 'active',
    ]);
    $superadmin = User::factory()->superadmin()->create([
        'locale' => 'lt',
    ]);
    $user = User::factory()->for($organization)->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->actingAs($superadmin)
        ->get(UserResource::getUrl('view', ['record' => $user]))
        ->assertOk()
        ->assertSeeText('Vardas')
        ->assertSeeText('Būsena')
        ->assertSeeText('Aktyvus')
        ->assertSeeText('Organizacijos ID');
});

it('localizes known database content in the superadmin user dossier', function (): void {
    app()->setLocale('lt');

    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create([
        'locale' => 'lt',
    ]);
    $user = User::factory()->tenant()->for($organization)->create([
        'locale' => 'lt',
    ]);
    $building = Building::factory()->for($organization)->create([
        'name' => 'Demo Building 01-01',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Demo Unit 01-01',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($user, 'tenant')
        ->create();

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'name' => 'Operations Demo Meter',
            'type' => MeterType::ELECTRICITY,
            'unit' => 'kWh',
        ]);

    MeterReading::factory()
        ->for($organization)
        ->for($property)
        ->for($meter)
        ->for($user, 'submittedBy')
        ->create([
            'validation_status' => MeterReadingValidationStatus::VALID,
            'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
            'notes' => 'Seeded legacy operations reading.',
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($user, 'tenant')
        ->create([
            'notes' => 'Demo invoice 1 for Demo Unit 01-01',
        ]);

    $this->actingAs($superadmin)
        ->get(UserResource::getUrl('view', ['record' => $user]))
        ->assertOk()
        ->assertSeeText('Lietuvių')
        ->assertSeeText('Demonstracinis pastatas 01-01')
        ->assertSeeText('Demonstracinė patalpa 01-01')
        ->assertSeeText('Operacijų demonstracinis skaitiklis: Elektra')
        ->assertSeeText('Importuotas senos operacijų sistemos rodmuo.')
        ->assertSeeText('Demonstracinė sąskaita 1: Demonstracinė patalpa 01-01')
        ->assertDontSeeText('Demo Building 01-01')
        ->assertDontSeeText('Demo Unit 01-01')
        ->assertDontSeeText('Operations Demo Meter')
        ->assertDontSeeText('Demo invoice 1 for Demo Unit 01-01')
        ->assertDontSeeText('Seeded legacy operations reading.');
});
