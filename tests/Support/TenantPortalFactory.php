<?php

namespace Tests\Support;

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;

class TenantPortalFactory
{
    protected string $userName = 'Taylor Tenant';

    protected int $meterCount = 0;

    protected bool $createAssignment = false;

    protected bool $createReadings = false;

    protected int $unpaidInvoiceCount = 0;

    protected int $paidInvoiceCount = 0;

    protected ?string $paymentInstructions = null;

    protected ?string $invoiceFooter = null;

    protected bool $seedDefaultPaymentInstructions = true;

    protected ?string $billingContactName = null;

    protected ?string $billingContactEmail = null;

    protected ?string $billingContactPhone = null;

    public static function new(): self
    {
        return new self;
    }

    public function withUserName(string $userName): self
    {
        $this->userName = $userName;

        return $this;
    }

    public function withAssignedProperty(): self
    {
        $this->createAssignment = true;

        return $this;
    }

    public function withMeters(int $meterCount = 2): self
    {
        $this->createAssignment = true;
        $this->meterCount = $meterCount;

        return $this;
    }

    public function withReadings(): self
    {
        $this->createAssignment = true;
        $this->createReadings = true;

        if ($this->meterCount === 0) {
            $this->meterCount = 2;
        }

        return $this;
    }

    public function withUnpaidInvoices(int $count = 2): self
    {
        $this->createAssignment = true;
        $this->unpaidInvoiceCount = $count;

        return $this;
    }

    public function withPaidInvoices(int $count = 1): self
    {
        $this->createAssignment = true;
        $this->paidInvoiceCount = $count;

        return $this;
    }

    public function withPaymentInstructions(string $instructions = 'Pay by bank transfer or at the office.'): self
    {
        $this->seedDefaultPaymentInstructions = false;
        $this->paymentInstructions = $instructions;

        return $this;
    }

    public function withInvoiceFooter(string $invoiceFooter): self
    {
        $this->seedDefaultPaymentInstructions = false;
        $this->invoiceFooter = $invoiceFooter;

        return $this;
    }

    public function withoutPaymentInstructions(): self
    {
        $this->seedDefaultPaymentInstructions = false;
        $this->paymentInstructions = null;
        $this->invoiceFooter = null;

        return $this;
    }

    public function withBillingContact(
        ?string $name = 'Billing Team',
        ?string $email = 'billing@example.com',
        ?string $phone = '+37060000000',
    ): self {
        $this->billingContactName = $name;
        $this->billingContactEmail = $email;
        $this->billingContactPhone = $phone;

        return $this;
    }

    public function create(): TenantPortalFixture
    {
        $organization = Organization::factory()->create();
        $building = Building::factory()->for($organization)->create([
            'address_line_1' => '123 Garden Street',
            'city' => 'Vilnius',
            'postal_code' => '01100',
            'country_code' => 'LT',
        ]);
        $property = Property::factory()->for($organization)->for($building)->create([
            'name' => 'Apartment 12',
            'unit_number' => '12',
        ]);
        $user = User::factory()->tenant()->create([
            'organization_id' => $organization->id,
            'name' => $this->userName,
        ]);

        $settings = OrganizationSetting::factory()->for($organization)->create([
            'billing_contact_name' => $this->billingContactName,
            'billing_contact_email' => $this->billingContactEmail,
            'billing_contact_phone' => $this->billingContactPhone,
            'payment_instructions' => $this->seedDefaultPaymentInstructions
                ? 'Pay by bank transfer or at the office.'
                : $this->paymentInstructions,
            'invoice_footer' => $this->invoiceFooter,
        ]);

        if ($this->createAssignment) {
            PropertyAssignment::factory()
                ->for($organization)
                ->for($property)
                ->for($user, 'tenant')
                ->create([
                    'assigned_at' => now()->subMonths(6),
                    'unassigned_at' => null,
                ]);
        }

        $meters = Collection::make();

        if ($this->meterCount > 0) {
            $meters = Meter::factory()
                ->count($this->meterCount)
                ->for($organization)
                ->for($property)
                ->sequence(fn (Sequence $sequence) => [
                    'name' => 'Meter '.($sequence->index + 1),
                    'identifier' => 'TEN-'.str_pad((string) ($sequence->index + 1), 3, '0', STR_PAD_LEFT),
                ])
                ->create()
                ->collect();
        }

        $readings = Collection::make();

        if ($this->createReadings) {
            $readings = $meters->flatMap(function (Meter $meter) use ($organization, $property, $user): array {
                return [
                    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
                        'submitted_by_user_id' => $user->id,
                        'reading_value' => 100.250,
                        'reading_date' => now()->subMonth()->endOfMonth()->toDateString(),
                        'validation_status' => MeterReadingValidationStatus::VALID,
                        'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
                    ]),
                    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
                        'submitted_by_user_id' => $user->id,
                        'reading_value' => 145.500,
                        'reading_date' => now()->startOfMonth()->addDays(2)->toDateString(),
                        'validation_status' => MeterReadingValidationStatus::VALID,
                        'submission_method' => MeterReadingSubmissionMethod::TENANT_PORTAL,
                    ]),
                ];
            })->collect();
        }

        $invoices = Collection::make();

        if ($this->unpaidInvoiceCount > 0) {
            $invoices = $invoices->merge(
                Invoice::factory()
                    ->count($this->unpaidInvoiceCount)
                    ->for($organization)
                    ->for($property)
                    ->for($user, 'tenant')
                    ->sequence(fn (Sequence $sequence) => [
                        'invoice_number' => 'UNPAID-'.str_pad((string) ($sequence->index + 1), 3, '0', STR_PAD_LEFT),
                        'status' => $sequence->index === 0 ? InvoiceStatus::OVERDUE : InvoiceStatus::FINALIZED,
                        'total_amount' => 75 + ($sequence->index * 25),
                        'amount_paid' => 0,
                        'due_date' => now()->addDays(14)->toDateString(),
                        'finalized_at' => now()->subDays(2),
                    ])
                    ->create()
                    ->collect()
            );
        }

        if ($this->paidInvoiceCount > 0) {
            $invoices = $invoices->merge(
                Invoice::factory()
                    ->count($this->paidInvoiceCount)
                    ->for($organization)
                    ->for($property)
                    ->for($user, 'tenant')
                    ->sequence(fn (Sequence $sequence) => [
                        'invoice_number' => 'PAID-'.str_pad((string) ($sequence->index + 1), 3, '0', STR_PAD_LEFT),
                        'status' => InvoiceStatus::PAID,
                        'total_amount' => 60 + ($sequence->index * 10),
                        'amount_paid' => 60 + ($sequence->index * 10),
                        'paid_at' => now()->subDay(),
                    ])
                    ->create()
                    ->collect()
            );
        }

        return new TenantPortalFixture(
            organization: $organization,
            settings: $settings,
            building: $building,
            property: $property->fresh(),
            user: $user->fresh(),
            meters: $meters,
            readings: $readings,
            invoices: $invoices,
        );
    }
}

class TenantPortalFixture
{
    /**
     * @param  Collection<int, Meter>  $meters
     * @param  Collection<int, MeterReading>  $readings
     * @param  Collection<int, Invoice>  $invoices
     */
    public function __construct(
        public Organization $organization,
        public OrganizationSetting $settings,
        public Building $building,
        public Property $property,
        public User $user,
        public Collection $meters,
        public Collection $readings,
        public Collection $invoices,
    ) {}
}
