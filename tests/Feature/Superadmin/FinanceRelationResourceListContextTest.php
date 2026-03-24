<?php

use App\Filament\Resources\InvoiceEmailLogs\Pages\ListInvoiceEmailLogs;
use App\Filament\Resources\InvoicePayments\Pages\ListInvoicePayments;
use App\Filament\Resources\InvoiceReminderLogs\Pages\ListInvoiceReminderLogs;
use App\Filament\Resources\SubscriptionPayments\Pages\ListSubscriptionPayments;
use App\Filament\Resources\SubscriptionRenewals\Pages\ListSubscriptionRenewals;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows organization context on the invoice payments list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Ivy Square']);
    $organizationB = Organization::factory()->create(['name' => 'Juniper Hall']);

    $invoiceA = Invoice::factory()->for($organizationA)->create();
    $invoiceB = Invoice::factory()->for($organizationB)->create();

    $paymentA = InvoicePayment::factory()->for($invoiceA)->for($organizationA)->create([
        'reference' => 'PAY-IVY-001',
    ]);

    $paymentB = InvoicePayment::factory()->for($invoiceB)->for($organizationB)->create([
        'reference' => 'PAY-JUN-001',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.invoice-payments.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($paymentA->reference)
        ->assertSeeText($paymentB->reference);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoice-payments.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListInvoicePayments::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$paymentA, $paymentB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$paymentA])
        ->assertCanNotSeeTableRecords([$paymentB]);
});

it('shows organization context on the invoice reminder logs list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Kingfisher Court']);
    $organizationB = Organization::factory()->create(['name' => 'Linden Park']);

    $invoiceA = Invoice::factory()->for($organizationA)->create();
    $invoiceB = Invoice::factory()->for($organizationB)->create();

    $logA = InvoiceReminderLog::factory()->for($invoiceA)->for($organizationA)->create([
        'recipient_email' => 'billing-a@example.test',
    ]);

    $logB = InvoiceReminderLog::factory()->for($invoiceB)->for($organizationB)->create([
        'recipient_email' => 'billing-b@example.test',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.invoice-reminder-logs.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($logA->recipient_email)
        ->assertSeeText($logB->recipient_email);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoice-reminder-logs.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListInvoiceReminderLogs::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$logA, $logB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$logA])
        ->assertCanNotSeeTableRecords([$logB]);
});

it('shows organization context on the invoice email logs list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Maple Landing']);
    $organizationB = Organization::factory()->create(['name' => 'North Pier']);

    $invoiceA = Invoice::factory()->for($organizationA)->create();
    $invoiceB = Invoice::factory()->for($organizationB)->create();

    $logA = InvoiceEmailLog::factory()->for($invoiceA)->for($organizationA)->create([
        'subject' => 'Invoice for Maple Landing',
    ]);

    $logB = InvoiceEmailLog::factory()->for($invoiceB)->for($organizationB)->create([
        'subject' => 'Invoice for North Pier',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.invoice-email-logs.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($logA->subject)
        ->assertSeeText($logB->subject);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoice-email-logs.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListInvoiceEmailLogs::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$logA, $logB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$logA])
        ->assertCanNotSeeTableRecords([$logB]);
});

it('shows organization context on the subscription payments list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Oak Terrace']);
    $organizationB = Organization::factory()->create(['name' => 'Pine Wharf']);

    $subscriptionA = Subscription::factory()->for($organizationA)->create();
    $subscriptionB = Subscription::factory()->for($organizationB)->create();

    $paymentA = SubscriptionPayment::factory()->for($organizationA)->for($subscriptionA)->create([
        'reference' => 'SUB-OAK-001',
    ]);

    $paymentB = SubscriptionPayment::factory()->for($organizationB)->for($subscriptionB)->create([
        'reference' => 'SUB-PINE-001',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscription-payments.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($paymentA->reference)
        ->assertSeeText($paymentB->reference);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.subscription-payments.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListSubscriptionPayments::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$paymentA, $paymentB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$paymentA])
        ->assertCanNotSeeTableRecords([$paymentB]);
});

it('shows organization context on the subscription renewals list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Quarry Heights']);
    $organizationB = Organization::factory()->create(['name' => 'River Point']);

    $subscriptionA = Subscription::factory()->for($organizationA)->create();
    $subscriptionB = Subscription::factory()->for($organizationB)->create();

    $renewalA = SubscriptionRenewal::factory()->for($subscriptionA)->create([
        'period' => 'monthly',
    ]);

    $renewalB = SubscriptionRenewal::factory()->for($subscriptionB)->create([
        'period' => 'annually',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscription-renewals.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($renewalA->period)
        ->assertSeeText($renewalB->period);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.subscription-renewals.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListSubscriptionRenewals::class)
        ->assertTableColumnExists('subscription.organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$renewalA, $renewalB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$renewalA])
        ->assertCanNotSeeTableRecords([$renewalB]);
});
