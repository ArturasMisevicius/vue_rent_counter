<?php

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use App\Notifications\Billing\InvoiceReadyForTenantNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

it('prepares a submitted reading request draft from tenant readings for admin review', function (): void {
    Carbon::setTestNow('2026-05-31 12:00:00');
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Review Unit',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => '2026-01-01 00:00:00',
            'unassigned_at' => null,
        ]);

    $provider = Provider::factory()->for($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = Tariff::factory()->for($provider)->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 2.00,
        ],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Water',
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::WATER,
    ]);

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->for($utilityService)
        ->for($provider)
        ->for($tariff)
        ->create([
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::BY_CONSUMPTION,
            'rate_schedule' => ['unit_rate' => 2.00],
            'is_shared_service' => false,
            'effective_from' => '2026-01-01',
            'effective_until' => null,
        ]);

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'type' => MeterType::WATER,
            'unit' => 'm3',
        ]);

    MeterReading::factory()
        ->for($organization)
        ->for($property)
        ->for($meter)
        ->create([
            'reading_value' => 100,
            'reading_date' => '2026-04-30',
            'validation_status' => MeterReadingValidationStatus::VALID,
        ]);

    $result = app(OpenReadingInvoiceCycleAction::class)->handle($organization, [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'due_date' => '2026-06-14',
    ], $admin);

    /** @var Invoice $invoice */
    $invoice = $result['created']->sole();

    Livewire::actingAs($tenant)
        ->withQueryParams(['invoice' => (string) $invoice->id])
        ->test(SubmitReadingPage::class)
        ->set('readingDate', '2026-05-31')
        ->set("readings.{$meter->id}.value", '125')
        ->call('submit')
        ->assertHasNoErrors();

    expect($invoice->fresh()->approval_status)->toBe('readings_submitted');

    Livewire::actingAs($admin)
        ->test(EditInvoice::class, ['record' => $invoice->id])
        ->assertActionVisible('prepareFromReadings')
        ->callAction('prepareFromReadings')
        ->assertHasNoActionErrors();

    $preparedInvoice = $invoice->fresh(['invoiceItems', 'billingRecords']);

    expect($preparedInvoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and($preparedInvoice->approval_status)->toBe('ready_for_review')
        ->and((float) $preparedInvoice->total_amount)->toBe(50.0)
        ->and($preparedInvoice->invoiceItems)->toHaveCount(1)
        ->and($preparedInvoice->billingRecords)->toHaveCount(1)
        ->and($preparedInvoice->approval_metadata['prepared_by_user_id'] ?? null)->toBe($admin->id)
        ->and($preparedInvoice->approval_metadata['prepared_invoice_item_count'] ?? null)->toBe(1);

    $this->actingAs($admin);

    $finalizedInvoice = app(FinalizeInvoiceAction::class)->handle($preparedInvoice);

    expect($finalizedInvoice->status)->toBe(InvoiceStatus::FINALIZED)
        ->and($finalizedInvoice->approval_status)->toBe('approved')
        ->and($finalizedInvoice->approved_by)->toBe($admin->id);

    Notification::assertSentTo(
        $tenant,
        InvoiceReadyForTenantNotification::class,
        function (InvoiceReadyForTenantNotification $notification, array $channels) use ($finalizedInvoice, $tenant): bool {
            $payload = $notification->toArray($tenant);

            return $channels === ['database']
                && $payload['invoice_id'] === $finalizedInvoice->id
                && $payload['url'] === route('filament.admin.pages.tenant-invoice-history', [], false).'#tenant-invoice-'.$finalizedInvoice->id;
        },
    );
});
