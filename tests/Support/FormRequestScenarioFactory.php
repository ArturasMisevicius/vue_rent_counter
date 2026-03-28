<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\TariffType;
use App\Enums\UnitOfMeasurement;
use App\Http\Requests\Admin\Buildings\BuildingRequest;
use App\Http\Requests\Admin\Invoices\CreateInvoiceDraftRequest;
use App\Http\Requests\Admin\Invoices\GenerateBulkInvoicesRequest;
use App\Http\Requests\Admin\Invoices\PreviewInvoiceDraftRequest;
use App\Http\Requests\Admin\Invoices\ProcessPaymentRequest;
use App\Http\Requests\Admin\Invoices\SaveInvoiceDraftRequest;
use App\Http\Requests\Admin\Invoices\SendInvoiceEmailRequest;
use App\Http\Requests\Admin\Kyc\RejectKycProfileRequest;
use App\Http\Requests\Admin\MeterReadings\RejectMeterReadingRequest;
use App\Http\Requests\Admin\MeterReadings\StoreMeterReadingRequest as AdminStoreMeterReadingRequest;
use App\Http\Requests\Admin\MeterReadings\UpdateMeterReadingRequest;
use App\Http\Requests\Admin\Meters\MeterRequest;
use App\Http\Requests\Admin\Properties\PropertyRequest;
use App\Http\Requests\Admin\Properties\StorePropertyRequest;
use App\Http\Requests\Admin\Providers\ProviderRequest;
use App\Http\Requests\Admin\Reports\ConsumptionReportRequest;
use App\Http\Requests\Admin\Reports\ExportReportRequest;
use App\Http\Requests\Admin\Reports\MeterComplianceReportRequest;
use App\Http\Requests\Admin\Reports\OutstandingBalancesReportRequest;
use App\Http\Requests\Admin\Reports\ReportExportRequest;
use App\Http\Requests\Admin\Reports\RevenueReportRequest;
use App\Http\Requests\Admin\Settings\RenewSubscriptionRequest;
use App\Http\Requests\Admin\Settings\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Admin\Settings\UpdateOrganizationSettingsRequest;
use App\Http\Requests\Admin\Tariffs\TariffRequest;
use App\Http\Requests\Admin\Tenants\ReassignTenantRequest;
use App\Http\Requests\Admin\Tenants\StoreTenantRequest;
use App\Http\Requests\Admin\Tenants\UpdateTenantRequest;
use App\Http\Requests\Admin\UtilityServices\UtilityServiceRequest;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Http\Requests\Auth\CompleteOnboardingRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Preferences\SetLocaleRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpsertKycProfileRequest;
use App\Http\Requests\Security\CspViolationRequest;
use App\Http\Requests\Shell\SearchQueryRequest;
use App\Http\Requests\Superadmin\Organizations\ImpersonateUserRequest;
use App\Http\Requests\Superadmin\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Superadmin\Organizations\UpdateOrganizationRequest;
use App\Http\Requests\Superadmin\Security\BlockIpAddressRequest;
use App\Http\Requests\Superadmin\Subscriptions\ExtendSubscriptionExpiryRequest;
use App\Http\Requests\Superadmin\Subscriptions\StoreOrganizationSubscriptionRequest;
use App\Http\Requests\Superadmin\Subscriptions\UpdateOrganizationSubscriptionRequest;
use App\Http\Requests\Superadmin\Subscriptions\UpgradeSubscriptionPlanRequest;
use App\Http\Requests\Superadmin\SystemConfiguration\UpdateSystemSettingRequest;
use App\Http\Requests\Tenant\InvoiceHistoryFilterRequest;
use App\Http\Requests\Tenant\PropertyHistoryFilterRequest;
use App\Http\Requests\Tenant\StoreMeterReadingRequest as TenantStoreMeterReadingRequest;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use ReflectionMethod;

final class FormRequestScenarioFactory
{
    /**
     * @return array<string, array{
     *     request: callable(array<string, mixed>): FormRequest,
     *     valid: callable(array<string, mixed>): array<string, mixed>,
     *     required: list<string>,
     *     authorize: array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool},
     *     invalid?: array<string, callable(array<string, mixed>, array<string, mixed>): array{field: string, input: array<string, mixed>}>
     * }>
     */
    public static function scenarios(): array
    {
        return [
            'AcceptInvitationRequest' => [
                'request' => static fn (array $context): FormRequest => new AcceptInvitationRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Tenant User',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'required' => ['name', 'password'],
                'authorize' => self::allRoles(true),
            ],
            'CompleteOnboardingRequest' => [
                'request' => static fn (array $context): FormRequest => new CompleteOnboardingRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'North Harbor Estates',
                ],
                'required' => ['name'],
                'authorize' => self::allRoles(true),
            ],
            'CspViolationRequest' => [
                'request' => static fn (array $context): FormRequest => new CspViolationRequest,
                'valid' => static fn (array $context): array => [
                    'csp-report' => [
                        'document-uri' => 'https://tenanto.test/app',
                        'violated-directive' => 'script-src-elem',
                        'effective-directive' => 'script-src',
                        'blocked-uri' => 'https://cdn.example.test/script.js',
                    ],
                ],
                'required' => [],
                'authorize' => self::allRoles(true),
                'invalid' => [
                    'csp report must be an array' => static fn (array $valid, array $context): array => [
                        'field' => 'csp-report',
                        'input' => self::withField($valid, 'csp-report', 'not-an-array'),
                    ],
                ],
            ],
            'ForgotPasswordRequest' => [
                'request' => static fn (array $context): FormRequest => new ForgotPasswordRequest,
                'valid' => static fn (array $context): array => [
                    'email' => 'resident@example.com',
                ],
                'required' => ['email'],
                'authorize' => self::allRoles(true),
            ],
            'LoginRequest' => [
                'request' => static fn (array $context): FormRequest => new LoginRequest,
                'valid' => static fn (array $context): array => [
                    'email' => $context['tenant']->email,
                    'password' => 'password',
                ],
                'required' => ['email', 'password'],
                'authorize' => self::allRoles(true),
            ],
            'RegisterRequest' => [
                'request' => static fn (array $context): FormRequest => new RegisterRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'New Owner',
                    'email' => 'new-owner@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'required' => ['name', 'email', 'password'],
                'authorize' => self::allRoles(true),
                'invalid' => [
                    'email unique' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', $context['duplicateUser']->email),
                    ],
                    'email disposable' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', 'new-owner@10minutemail.com'),
                    ],
                ],
            ],
            'ResetPasswordRequest' => [
                'request' => static fn (array $context): FormRequest => new ResetPasswordRequest,
                'valid' => static fn (array $context): array => [
                    'token' => 'reset-token',
                    'email' => $context['tenant']->email,
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
                'required' => ['token', 'email', 'password'],
                'authorize' => self::allRoles(true),
            ],
            'SetLocaleRequest' => [
                'request' => static fn (array $context): FormRequest => new SetLocaleRequest,
                'valid' => static fn (array $context): array => [
                    'locale' => 'lt',
                ],
                'required' => ['locale'],
                'authorize' => self::allRoles(true),
            ],
            'UpdatePasswordRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdatePasswordRequest,
                'valid' => static fn (array $context): array => [
                    'current_password' => 'password',
                    'password' => 'new-password123',
                    'password_confirmation' => 'new-password123',
                ],
                'required' => ['current_password', 'password'],
                'authorize' => self::authenticatedOnly(),
            ],
            'UpdateProfileRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateProfileRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Updated Name',
                    'email' => $context['admin']->email,
                    'locale' => 'ru',
                ],
                'required' => ['name', 'email', 'locale'],
                'authorize' => self::authenticatedOnly(),
                'invalid' => [
                    'email unique' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', $context['duplicateUser']->email),
                    ],
                    'email disposable' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', 'profile-owner@10minutemail.com'),
                    ],
                ],
            ],
            'UpsertKycProfileRequest' => [
                'request' => static fn (array $context): FormRequest => new UpsertKycProfileRequest,
                'valid' => static fn (array $context): array => [
                    'full_legal_name' => 'Taylor Tenant',
                    'birth_date' => now()->subYears(30)->toDateString(),
                    'nationality' => 'Lithuanian',
                    'secondary_contact_email' => 'contact@example.com',
                    'facial_recognition_consent' => true,
                    'payment_history_score' => 80,
                    'internal_credit_score' => 640,
                ],
                'required' => ['full_legal_name'],
                'authorize' => self::authenticatedOnly(),
            ],
            'SearchQueryRequest' => [
                'request' => static fn (array $context): FormRequest => new SearchQueryRequest,
                'valid' => static fn (array $context): array => [
                    'query' => 'meter trends',
                ],
                'required' => [],
                'authorize' => self::authenticatedOnly(),
            ],
            'InvoiceHistoryFilterRequest' => [
                'request' => static fn (array $context): FormRequest => new InvoiceHistoryFilterRequest,
                'valid' => static fn (array $context): array => [
                    'selectedStatus' => 'unpaid',
                ],
                'required' => [],
                'authorize' => self::tenantOnly(),
            ],
            'PropertyHistoryFilterRequest' => [
                'request' => static fn (array $context): FormRequest => new PropertyHistoryFilterRequest,
                'valid' => static fn (array $context): array => [
                    'selectedYear' => now()->format('Y'),
                    'selectedMonth' => '3',
                ],
                'required' => [],
                'authorize' => self::tenantOnly(),
            ],
            'StoreMeterReadingRequest' => [
                'request' => static fn (array $context): FormRequest => new TenantStoreMeterReadingRequest,
                'valid' => static fn (array $context): array => [
                    'meterId' => (string) $context['meter']->id,
                    'readingValue' => '123.45',
                    'readingDate' => now()->toDateString(),
                    'notes' => 'Submitted from portal.',
                ],
                'required' => ['meterId', 'readingValue', 'readingDate'],
                'authorize' => self::tenantOnly(),
            ],
            'AdminStoreMeterReadingRequest' => [
                'request' => static fn (array $context): FormRequest => new AdminStoreMeterReadingRequest,
                'valid' => static fn (array $context): array => [
                    'reading_value' => '145.50',
                    'reading_date' => now()->toDateString(),
                    'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL->value,
                    'notes' => 'Entered by staff.',
                ],
                'required' => ['reading_value', 'reading_date', 'submission_method'],
                'authorize' => self::adminLikeAndTenantOnly(),
            ],
            'StoreOrganizationRequest' => [
                'request' => static fn (array $context): FormRequest => new StoreOrganizationRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Aurora Plaza',
                    'owner_email' => 'owner@example.com',
                    'plan' => SubscriptionPlan::PROFESSIONAL->value,
                    'duration' => SubscriptionDuration::YEARLY->value,
                ],
                'required' => ['name', 'owner_email', 'plan', 'duration'],
                'authorize' => self::superadminOnly(),
                'invalid' => [
                    'owner email disposable' => static fn (array $valid, array $context): array => [
                        'field' => 'owner_email',
                        'input' => self::withField($valid, 'owner_email', 'owner@10minutemail.com'),
                    ],
                ],
            ],
            'UpdateOrganizationRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateOrganizationRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Updated Aurora Plaza',
                    'owner_email' => 'updated-owner@example.com',
                    'owner_name' => 'Updated Owner',
                    'plan' => SubscriptionPlan::ENTERPRISE->value,
                ],
                'required' => ['name'],
                'authorize' => self::superadminOnly(),
                'invalid' => [
                    'owner email disposable' => static fn (array $valid, array $context): array => [
                        'field' => 'owner_email',
                        'input' => self::withField($valid, 'owner_email', 'updated-owner@10minutemail.com'),
                    ],
                ],
            ],
            'ImpersonateUserRequest' => [
                'request' => static fn (array $context): FormRequest => new ImpersonateUserRequest,
                'valid' => static fn (array $context): array => [
                    'user_id' => $context['tenant']->id,
                ],
                'required' => ['user_id'],
                'authorize' => self::superadminOnly(),
            ],
            'BlockIpAddressRequest' => [
                'request' => static fn (array $context): FormRequest => new BlockIpAddressRequest,
                'valid' => static fn (array $context): array => [
                    'ip_address' => '192.168.10.25',
                    'reason' => 'Rate limited',
                    'blocked_by_user_id' => $context['superadmin']->id,
                    'blocked_until' => now()->addDay()->toDateTimeString(),
                ],
                'required' => ['ip_address', 'reason', 'blocked_by_user_id'],
                'authorize' => self::superadminOnly(),
            ],
            'ExtendSubscriptionExpiryRequest' => [
                'request' => static fn (array $context): FormRequest => new ExtendSubscriptionExpiryRequest,
                'valid' => static fn (array $context): array => [
                    'expires_at' => now()->addMonth()->toDateString(),
                ],
                'required' => ['expires_at'],
                'authorize' => self::superadminOnly(),
            ],
            'StoreOrganizationSubscriptionRequest' => [
                'request' => static fn (array $context): FormRequest => new StoreOrganizationSubscriptionRequest,
                'valid' => static fn (array $context): array => [
                    'plan' => SubscriptionPlan::PROFESSIONAL->value,
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'starts_at' => now()->startOfDay()->toDateString(),
                    'expires_at' => now()->addMonth()->startOfDay()->toDateString(),
                ],
                'required' => ['plan', 'status', 'starts_at', 'expires_at'],
                'authorize' => self::superadminOnly(),
            ],
            'UpdateOrganizationSubscriptionRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateOrganizationSubscriptionRequest,
                'valid' => static fn (array $context): array => [
                    'plan' => SubscriptionPlan::ENTERPRISE->value,
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'starts_at' => now()->startOfDay()->toDateString(),
                    'expires_at' => now()->addMonths(2)->startOfDay()->toDateString(),
                ],
                'required' => ['plan', 'status', 'starts_at', 'expires_at'],
                'authorize' => self::superadminOnly(),
            ],
            'UpgradeSubscriptionPlanRequest' => [
                'request' => static fn (array $context): FormRequest => new UpgradeSubscriptionPlanRequest,
                'valid' => static fn (array $context): array => [
                    'plan' => SubscriptionPlan::PROFESSIONAL->value,
                ],
                'required' => ['plan'],
                'authorize' => self::superadminOnly(),
            ],
            'UpdateSystemSettingRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateSystemSettingRequest,
                'valid' => static fn (array $context): array => [
                    'value' => 'enabled',
                ],
                'required' => ['value'],
                'authorize' => self::superadminOnly(),
                'invalid' => [
                    'value string' => static fn (array $valid, array $context): array => [
                        'field' => 'value',
                        'input' => self::withField($valid, 'value', ['invalid']),
                    ],
                ],
            ],
            'RenewSubscriptionRequest' => [
                'request' => static fn (array $context): FormRequest => new RenewSubscriptionRequest,
                'valid' => static fn (array $context): array => [
                    'plan' => SubscriptionPlan::BASIC->value,
                    'duration' => SubscriptionDuration::MONTHLY->value,
                ],
                'required' => ['plan', 'duration'],
                'authorize' => self::adminOnly(),
            ],
            'UpdateNotificationPreferencesRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateNotificationPreferencesRequest,
                'valid' => static fn (array $context): array => [
                    'new_invoice_generated' => true,
                    'invoice_overdue' => false,
                    'tenant_submits_reading' => true,
                    'subscription_expiring' => false,
                ],
                'required' => ['new_invoice_generated', 'invoice_overdue', 'tenant_submits_reading', 'subscription_expiring'],
                'authorize' => self::adminOnly(),
            ],
            'UpdateOrganizationSettingsRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateOrganizationSettingsRequest,
                'valid' => static fn (array $context): array => [
                    'organization_name' => 'Harbor Heights',
                    'billing_contact_email' => 'billing@example.com',
                    'invoice_footer' => 'Thank you for your business.',
                ],
                'required' => ['organization_name'],
                'authorize' => self::adminOnly(),
            ],
            'BuildingRequest' => [
                'request' => static fn (array $context): FormRequest => new BuildingRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Baltic Residence',
                    'address_line_1' => 'Main street 1',
                    'address_line_2' => 'Tower A',
                    'city' => 'Vilnius',
                    'postal_code' => 'LT-01100',
                    'country_code' => 'lt',
                ],
                'required' => ['name', 'address_line_1'],
                'authorize' => self::adminLikeOnly(),
            ],
            'ProcessPaymentRequest' => [
                'request' => static fn (array $context): FormRequest => new ProcessPaymentRequest,
                'valid' => static fn (array $context): array => [
                    'amount_paid' => '45.50',
                    'paid_amount' => '45.50',
                    'payment_reference' => 'PAY-12345',
                    'paid_at' => now()->toDateTimeString(),
                ],
                'required' => [],
                'authorize' => self::adminLikeOnly(),
            ],
            'GenerateBulkInvoicesRequest' => [
                'request' => static fn (array $context): FormRequest => new GenerateBulkInvoicesRequest,
                'valid' => static fn (array $context): array => [
                    'billing_period_start' => now()->startOfMonth()->toDateString(),
                    'billing_period_end' => now()->endOfMonth()->toDateString(),
                    'due_date' => now()->endOfMonth()->addDays(14)->toDateString(),
                    'selected_assignments' => ['assignment-1'],
                ],
                'required' => ['billing_period_start', 'billing_period_end', 'due_date'],
                'authorize' => self::adminLikeOnly(),
            ],
            'CreateInvoiceDraftRequest' => [
                'request' => static fn (array $context): FormRequest => new CreateInvoiceDraftRequest,
                'valid' => static fn (array $context): array => [
                    'tenant_user_id' => $context['tenant']->id,
                    'billing_period_start' => now()->startOfMonth()->toDateString(),
                    'billing_period_end' => now()->endOfMonth()->toDateString(),
                    'due_date' => now()->endOfMonth()->addDays(14)->toDateString(),
                    'items' => [[
                        'description' => 'Cold water',
                        'period' => now()->format('F Y'),
                        'unit' => 'm3',
                        'quantity' => '14.25',
                        'rate' => '1.55',
                        'total' => '22.09',
                    ]],
                    'adjustments' => [[
                        'label' => 'Service discount',
                        'amount' => '-1.00',
                    ]],
                    'notes' => 'Previewed draft invoice',
                ],
                'required' => ['tenant_user_id', 'billing_period_start', 'billing_period_end', 'items'],
                'authorize' => self::adminLikeOnly(),
            ],
            'PreviewInvoiceDraftRequest' => [
                'request' => static fn (array $context): FormRequest => new PreviewInvoiceDraftRequest,
                'valid' => static fn (array $context): array => [
                    'tenant_user_id' => $context['tenant']->id,
                    'billing_period_start' => now()->startOfMonth()->toDateString(),
                    'billing_period_end' => now()->endOfMonth()->toDateString(),
                ],
                'required' => ['tenant_user_id', 'billing_period_start', 'billing_period_end'],
                'authorize' => self::adminLikeOnly(),
            ],
            'SaveInvoiceDraftRequest' => [
                'request' => static fn (array $context): FormRequest => new SaveInvoiceDraftRequest,
                'valid' => static fn (array $context): array => [
                    'invoice_number' => 'INV-2026-001',
                    'billing_period_start' => now()->startOfMonth()->toDateString(),
                    'billing_period_end' => now()->endOfMonth()->toDateString(),
                    'status' => InvoiceStatus::DRAFT->value,
                    'total_amount' => '199.99',
                    'amount_paid' => '0',
                    'paid_amount' => '0',
                    'due_date' => now()->addDays(14)->toDateString(),
                    'paid_at' => now()->toDateString(),
                    'payment_reference' => 'REF-2026',
                    'items' => [['name' => 'Water', 'amount' => 90]],
                    'notes' => 'Draft invoice',
                ],
                'required' => [],
                'authorize' => self::adminLikeOnly(),
            ],
            'SendInvoiceEmailRequest' => [
                'request' => static fn (array $context): FormRequest => new SendInvoiceEmailRequest,
                'valid' => static fn (array $context): array => [
                    'recipient_email' => $context['tenant']->email,
                ],
                'required' => ['recipient_email'],
                'authorize' => self::adminLikeOnly(),
            ],
            'RejectKycProfileRequest' => [
                'request' => static fn (array $context): FormRequest => new RejectKycProfileRequest,
                'valid' => static fn (array $context): array => [
                    'rejection_reason' => 'The submitted KYC documents need clearer scans.',
                ],
                'required' => ['rejection_reason'],
                'authorize' => self::adminLikeOnly(),
            ],
            'RejectMeterReadingRequest' => [
                'request' => static fn (array $context): FormRequest => new RejectMeterReadingRequest,
                'valid' => static fn (array $context): array => [
                    'reason' => 'The submitted reading requires a corrected value.',
                ],
                'required' => ['reason'],
                'authorize' => self::adminLikeOnly(),
            ],
            'UpdateMeterReadingRequest' => [
                'request' => static fn (array $context): FormRequest => new UpdateMeterReadingRequest,
                'valid' => static fn (array $context): array => [
                    'reading_value' => '240.50',
                    'reading_date' => now()->toDateString(),
                    'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL->value,
                    'notes' => 'Validated by manager',
                ],
                'required' => ['reading_value', 'reading_date', 'submission_method'],
                'authorize' => self::adminLikeOnly(),
            ],
            'MeterRequest' => [
                'request' => static fn (array $context): FormRequest => (new MeterRequest)->forOrganization($context['organization']->id),
                'valid' => static fn (array $context): array => [
                    'property_id' => $context['property']->id,
                    'name' => 'Kitchen Meter',
                    'identifier' => 'MTR-9000-AA',
                    'type' => MeterType::WATER->value,
                    'unit' => 'm3',
                    'status' => MeterStatus::ACTIVE->value,
                    'installed_at' => now()->subYear()->toDateString(),
                ],
                'required' => ['property_id', 'name', 'identifier', 'type', 'status'],
                'authorize' => self::adminLikeOnly(),
            ],
            'PropertyRequest' => [
                'request' => static fn (array $context): FormRequest => (new PropertyRequest)->forOrganization($context['organization']->id),
                'valid' => static fn (array $context): array => [
                    'building_id' => $context['building']->id,
                    'name' => 'Apartment 12',
                    'floor' => 1,
                    'unit_number' => '12',
                    'type' => PropertyType::APARTMENT->value,
                    'floor_area_sqm' => '58.50',
                ],
                'required' => ['building_id', 'name', 'type'],
                'authorize' => self::adminLikeOnly(),
            ],
            'StorePropertyRequest' => [
                'request' => static fn (array $context): FormRequest => (new StorePropertyRequest)->forOrganization($context['organization']->id),
                'valid' => static fn (array $context): array => [
                    'building_id' => $context['building']->id,
                    'name' => 'Apartment 12',
                    'floor' => 1,
                    'unit_number' => '12',
                    'type' => PropertyType::APARTMENT->value,
                    'floor_area_sqm' => '58.50',
                ],
                'required' => ['building_id', 'name', 'type'],
                'authorize' => self::adminLikeOnly(),
            ],
            'ProviderRequest' => [
                'request' => static fn (array $context): FormRequest => new ProviderRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Ignitis',
                    'service_type' => ServiceType::ELECTRICITY->value,
                    'contact_info' => [
                        'phone' => '+37060000000',
                        'email' => 'info@provider.test',
                        'website' => 'https://provider.test',
                    ],
                ],
                'required' => ['name', 'service_type'],
                'authorize' => self::adminLikeOnly(),
            ],
            'UtilityServiceRequest' => [
                'request' => static fn (array $context): FormRequest => new UtilityServiceRequest,
                'valid' => static fn (array $context): array => [
                    'name' => 'Cold Water',
                    'unit_of_measurement' => UnitOfMeasurement::CUBIC_METER->value,
                    'default_pricing_model' => PricingModel::CONSUMPTION_BASED->value,
                    'service_type_bridge' => ServiceType::WATER->value,
                    'description' => 'Cold water usage',
                    'is_active' => true,
                ],
                'required' => ['name', 'default_pricing_model', 'service_type_bridge'],
                'authorize' => self::adminLikeOnly(),
            ],
            'ConsumptionReportRequest' => self::reportScenario(ConsumptionReportRequest::class),
            'ExportReportRequest' => self::reportScenario(
                ExportReportRequest::class,
                ['format'],
                static fn (array $context): array => [
                    'format' => 'csv',
                ],
            ),
            'ReportExportRequest' => self::reportScenario(
                ReportExportRequest::class,
                ['format'],
                static fn (array $context): array => [
                    'format' => 'csv',
                ],
            ),
            'MeterComplianceReportRequest' => self::reportScenario(MeterComplianceReportRequest::class),
            'OutstandingBalancesReportRequest' => self::reportScenario(OutstandingBalancesReportRequest::class),
            'RevenueReportRequest' => self::reportScenario(RevenueReportRequest::class),
            'TariffRequest' => [
                'request' => static fn (array $context): FormRequest => (new TariffRequest)->forOrganization($context['organization']->id),
                'valid' => static fn (array $context): array => [
                    'provider_id' => $context['provider']->id,
                    'remote_id' => 'sync-001',
                    'name' => 'Standard Flat Tariff',
                    'configuration' => [
                        'type' => TariffType::FLAT->value,
                        'currency' => 'EUR',
                        'rate' => '0.25',
                    ],
                    'active_from' => now()->startOfMonth()->toDateString(),
                    'active_until' => now()->endOfMonth()->toDateString(),
                ],
                'required' => ['provider_id', 'name', 'configuration.type', 'configuration.currency', 'configuration.rate', 'active_from'],
                'authorize' => self::adminLikeOnly(),
            ],
            'ReassignTenantRequest' => [
                'request' => static fn (array $context): FormRequest => (new ReassignTenantRequest)->forOrganization($context['organization']->id),
                'valid' => static fn (array $context): array => [
                    'property_id' => $context['property']->id,
                    'unit_area_sqm' => '55.5',
                ],
                'required' => ['property_id'],
                'authorize' => self::adminLikeOnly(),
            ],
            'StoreTenantRequest' => [
                'request' => static fn (array $context): FormRequest => (new StoreTenantRequest)->forOrganization($context['organization']->id),
                'valid' => static fn (array $context): array => [
                    'name' => 'Portal Tenant',
                    'email' => 'portal-tenant@example.com',
                    'locale' => 'en',
                    'property_id' => $context['property']->id,
                    'unit_area_sqm' => '45.5',
                ],
                'required' => ['name', 'email', 'locale'],
                'authorize' => self::adminLikeOnly(),
                'invalid' => [
                    'email unique' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', $context['duplicateUser']->email),
                    ],
                    'email disposable' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', 'portal-tenant@10minutemail.com'),
                    ],
                ],
            ],
            'UpdateTenantRequest' => [
                'request' => static fn (array $context): FormRequest => (new UpdateTenantRequest)->forTenant($context['tenant']),
                'valid' => static fn (array $context): array => [
                    'name' => 'Updated Tenant',
                    'email' => $context['tenant']->email,
                    'locale' => 'lt',
                    'property_id' => $context['property']->id,
                    'unit_area_sqm' => '42',
                ],
                'required' => ['name', 'email', 'locale'],
                'authorize' => self::adminLikeOnly(),
                'invalid' => [
                    'email unique' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', $context['duplicateUser']->email),
                    ],
                    'email disposable' => static fn (array $valid, array $context): array => [
                        'field' => 'email',
                        'input' => self::withField($valid, 'email', 'tenant@10minutemail.com'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function context(): array
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $superadmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin()->create([
            'organization_id' => $organization->id,
        ]);
        $manager = User::factory()->manager()->create([
            'organization_id' => $organization->id,
        ]);
        $tenant = User::factory()->tenant()->create([
            'organization_id' => $organization->id,
        ]);
        $duplicateUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Subscription::factory()->for($organization)->active()->create([
            'property_limit_snapshot' => 25,
            'tenant_limit_snapshot' => 25,
            'meter_limit_snapshot' => 50,
            'invoice_limit_snapshot' => 100,
        ]);

        $building = Building::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $property = Property::factory()->create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
        ]);
        $otherProperty = Property::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);
        $provider = Provider::factory()->forOrganization($organization)->create();
        $otherProvider = Provider::factory()->forOrganization($otherOrganization)->create();
        $meter = Meter::factory()->create([
            'organization_id' => $organization->id,
            'property_id' => $property->id,
        ]);
        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'property_id' => $property->id,
            'tenant_user_id' => $tenant->id,
        ]);
        $existingOrganization = Organization::factory()->create();
        $systemSetting = SystemSetting::factory()->create();

        return compact(
            'organization',
            'otherOrganization',
            'superadmin',
            'admin',
            'manager',
            'tenant',
            'duplicateUser',
            'building',
            'property',
            'otherProperty',
            'provider',
            'otherProvider',
            'meter',
            'invoice',
            'existingOrganization',
            'systemSetting',
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $input
     */
    public static function validatorFor(FormRequest $request, array $context, array $input, ?Authenticatable $user): ValidatorContract
    {
        $preparedRequest = self::preparedRequest($request, $input, $user);

        return Validator::make(
            $preparedRequest->all(),
            $preparedRequest->rules(),
            $preparedRequest->messages(),
            $preparedRequest->attributes(),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $scenario
     * @return array<string, array{field: string, input: array<string, mixed>}>
     */
    public static function invalidCases(array $scenario, array $context): array
    {
        /** @var FormRequest $request */
        $request = ($scenario['request'])($context);
        /** @var array<string, mixed> $valid */
        $valid = ($scenario['valid'])($context);

        $cases = [];

        foreach ($request->rules() as $field => $rules) {
            foreach (Arr::wrap($rules) as $rule) {
                $generatedCase = self::generateInvalidCase($field, $rule, $valid, $context);

                if ($generatedCase === null) {
                    continue;
                }

                $cases[$generatedCase['name']] = [
                    'field' => $field,
                    'input' => $generatedCase['input'],
                ];
            }
        }

        foreach ($scenario['invalid'] ?? [] as $name => $builder) {
            $cases[$name] = $builder($valid, $context);
        }

        return $cases;
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    public static function authorizationMap(array $scenario): array
    {
        return $scenario['authorize'];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function userForRole(array $context, string $role): ?User
    {
        return match ($role) {
            'guest' => null,
            'superadmin' => $context['superadmin'],
            'admin' => $context['admin'],
            'manager' => $context['manager'],
            'tenant' => $context['tenant'],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function withField(array $input, string $field, mixed $value): array
    {
        Arr::set($input, $field, $value);

        if (str_ends_with($field, 'password')) {
            Arr::set($input, $field.'_confirmation', $value);
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function withoutField(array $input, string $field): array
    {
        Arr::forget($input, $field);

        if (str_ends_with($field, 'password')) {
            Arr::forget($input, $field.'_confirmation');
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function validatedPayload(FormRequest $request, array $context, array $input, ?Authenticatable $user): array
    {
        /** @var array<string, mixed> $validated */
        $validated = self::validatorFor($request, $context, $input, $user)->validate();

        return $validated;
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function allRoles(bool $allowed): array
    {
        return [
            'guest' => $allowed,
            'superadmin' => $allowed,
            'admin' => $allowed,
            'manager' => $allowed,
            'tenant' => $allowed,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function authenticatedOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => true,
            'admin' => true,
            'manager' => true,
            'tenant' => true,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function tenantOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => false,
            'admin' => false,
            'manager' => false,
            'tenant' => true,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function superadminOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => true,
            'admin' => false,
            'manager' => false,
            'tenant' => false,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function adminOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => false,
            'admin' => true,
            'manager' => false,
            'tenant' => false,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function adminManagerOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => false,
            'admin' => true,
            'manager' => true,
            'tenant' => false,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function adminLikeOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => true,
            'admin' => true,
            'manager' => true,
            'tenant' => false,
        ];
    }

    /**
     * @return array{guest: bool, superadmin: bool, admin: bool, manager: bool, tenant: bool}
     */
    private static function adminLikeAndTenantOnly(): array
    {
        return [
            'guest' => false,
            'superadmin' => true,
            'admin' => true,
            'manager' => true,
            'tenant' => true,
        ];
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     * @param  list<string>  $additionalRequired
     * @param  (callable(array<string, mixed>): array<string, mixed>)|null  $extraValid
     * @return array<string, mixed>
     */
    private static function reportScenario(string $requestClass, array $additionalRequired = [], ?callable $extraValid = null): array
    {
        return [
            'request' => static fn (array $context): FormRequest => new $requestClass,
            'valid' => static function (array $context) use ($extraValid): array {
                $payload = [
                    'start_date' => now()->startOfMonth()->toDateString(),
                    'end_date' => now()->endOfMonth()->toDateString(),
                    'meter_type' => MeterType::WATER->value,
                    'invoice_status' => InvoiceStatus::PAID->value,
                    'only_overdue' => false,
                    'compliance_state' => 'compliant',
                ];

                if ($extraValid !== null) {
                    $payload = [
                        ...$payload,
                        ...$extraValid($context),
                    ];
                }

                return $payload;
            },
            'required' => [
                'start_date',
                'end_date',
                'only_overdue',
                ...$additionalRequired,
            ],
            'authorize' => self::adminLikeOnly(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function preparedRequest(FormRequest $request, array $input, ?Authenticatable $user): FormRequest
    {
        $validationRequest = new ReflectionMethod($request, 'validationRequest');
        $validationRequest->setAccessible(true);

        /** @var FormRequest $preparedRequest */
        $preparedRequest = $validationRequest->invoke($request, $input, $user);

        $prepareForValidation = new ReflectionMethod($preparedRequest, 'prepareForValidation');
        $prepareForValidation->setAccessible(true);
        $prepareForValidation->invoke($preparedRequest);

        return $preparedRequest;
    }

    /**
     * @param  array<string, mixed>  $valid
     * @param  array<string, mixed>  $context
     * @return array{name: string, input: array<string, mixed>}|null
     */
    private static function generateInvalidCase(string $field, mixed $rule, array $valid, array $context): ?array
    {
        if ($rule instanceof RequiredIf) {
            return null;
        }

        if ($rule instanceof Enum) {
            return [
                'name' => $field.' enum',
                'input' => self::withField($valid, $field, '__invalid_enum__'),
            ];
        }

        if ($rule instanceof In) {
            return [
                'name' => $field.' in',
                'input' => self::withField($valid, $field, '__invalid_in__'),
            ];
        }

        if ($rule instanceof Exists) {
            return [
                'name' => $field.' exists',
                'input' => self::withField($valid, $field, 999999),
            ];
        }

        if ($rule instanceof Unique) {
            return null;
        }

        if ($rule instanceof Password) {
            return [
                'name' => $field.' password',
                'input' => self::withField($valid, $field, 'short'),
            ];
        }

        if (! is_string($rule)) {
            return null;
        }

        if (in_array($rule, ['required', 'nullable', 'sometimes'], true)) {
            return null;
        }

        if ($rule === 'array') {
            return [
                'name' => $field.' array',
                'input' => self::withField($valid, $field, 'invalid'),
            ];
        }

        if ($rule === 'boolean') {
            return [
                'name' => $field.' boolean',
                'input' => self::withField($valid, $field, 'not-a-boolean'),
            ];
        }

        if ($rule === 'integer') {
            return [
                'name' => $field.' integer',
                'input' => self::withField($valid, $field, 'abc'),
            ];
        }

        if ($rule === 'numeric') {
            return [
                'name' => $field.' numeric',
                'input' => self::withField($valid, $field, 'not-numeric'),
            ];
        }

        if ($rule === 'email' || str_starts_with($rule, 'email:')) {
            return [
                'name' => $field.' email',
                'input' => self::withField($valid, $field, 'not-an-email'),
            ];
        }

        if ($rule === 'url') {
            return [
                'name' => $field.' url',
                'input' => self::withField($valid, $field, 'not-a-url'),
            ];
        }

        if ($rule === 'date') {
            return [
                'name' => $field.' date',
                'input' => self::withField($valid, $field, 'not-a-date'),
            ];
        }

        if ($rule === 'ip') {
            return [
                'name' => $field.' ip',
                'input' => self::withField($valid, $field, '999.999.999.999'),
            ];
        }

        if ($rule === 'confirmed') {
            $input = self::withField($valid, $field, 'password123');
            Arr::set($input, $field.'_confirmation', 'mismatch-value');

            return [
                'name' => $field.' confirmed',
                'input' => $input,
            ];
        }

        if ($rule === 'current_password') {
            return [
                'name' => $field.' current_password',
                'input' => self::withField($valid, $field, 'wrong-password'),
            ];
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) str($rule)->after(':')->value();
            $currentValue = data_get($valid, $field);
            $invalidValue = is_numeric($currentValue)
                ? ($min === 0 ? -1 : $min - 1)
                : str_repeat('x', max($min - 1, 0));

            return [
                'name' => $field.' min',
                'input' => self::withField($valid, $field, $invalidValue),
            ];
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) str($rule)->after(':')->value();

            return [
                'name' => $field.' max',
                'input' => self::withField($valid, $field, str_repeat('x', $max + 1)),
            ];
        }

        if (str_starts_with($rule, 'size:')) {
            $size = (int) str($rule)->after(':')->value();

            return [
                'name' => $field.' size',
                'input' => self::withField($valid, $field, str_repeat('x', $size + 1)),
            ];
        }

        if (str_starts_with($rule, 'gt:')) {
            return [
                'name' => $field.' gt',
                'input' => self::withField($valid, $field, 0),
            ];
        }

        if (str_starts_with($rule, 'after_or_equal:')) {
            $referenceField = (string) str($rule)->after(':');
            $referenceValue = (string) data_get($valid, $referenceField, now()->toDateString());

            return [
                'name' => $field.' after_or_equal',
                'input' => self::withField($valid, $field, now()->parse($referenceValue)->subDay()->toDateString()),
            ];
        }

        if (str_starts_with($rule, 'unique:')) {
            if (str_contains($field, 'email')) {
                return [
                    'name' => $field.' unique',
                    'input' => self::withField($valid, $field, $context['duplicateUser']->email),
                ];
            }

            if ($field === 'slug') {
                return [
                    'name' => $field.' unique',
                    'input' => self::withField($valid, $field, $context['existingOrganization']->slug),
                ];
            }
        }

        return null;
    }
}
