<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Comment;
use App\Models\Currency;
use App\Models\EnhancedTask;
use App\Models\IntegrationHealthCheck;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Models\Property;
use App\Models\Provider;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\SystemConfiguration;
use App\Models\SystemHealthMetric;
use App\Models\Tag;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveTenantSeeder extends Seeder
{
    private const TENANT_ID = 1;

    private const PASSWORD = 'password';

    public function run(): void
    {
        fake()->seed(20260208);

        $this->seedFoundation();
        [$organization, $superadmin, $admin, $manager] = $this->seedIdentity();
        $assets = $this->seedAssets($admin, $manager);
        $billing = $this->seedBilling($manager, $assets['tenant_records']);

        $this->seedAuxiliary(
            $organization,
            $superadmin,
            $admin,
            $manager,
            $assets['tenant_records'],
            $billing['invoices'],
        );
    }

    /**
     * @return array{0: Organization, 1: User, 2: User, 3: User}
     */
    private function seedIdentity(): array
    {
        $superadmin = $this->upsertUser('superadmin@example.com', [
            'name' => 'System Superadmin',
            'tenant_id' => null,
            'property_id' => null,
            'parent_user_id' => null,
            'role' => UserRole::SUPERADMIN->value,
            'is_active' => true,
            'organization_name' => null,
            'currency' => 'EUR',
        ]);

        $organizationData = Organization::factory()->make([
            'id' => self::TENANT_ID,
            'name' => 'RentCounter Demo Organization',
            'slug' => 'rentcounter-demo-organization',
            'domain' => 'demo.rentcounter.test',
            'email' => 'admin@example.com',
            'primary_contact_email' => 'admin@example.com',
            'timezone' => 'Europe/Vilnius',
            'locale' => 'en',
            'currency' => 'EUR',
            'plan' => 'professional',
            'created_by_admin_id' => $superadmin->id,
            'created_by' => $superadmin->id,
        ])->getAttributes();

        $organization = Organization::query()->updateOrCreate(
            ['id' => self::TENANT_ID],
            $organizationData,
        );

        $admin = $this->upsertUser('admin@example.com', [
            'name' => 'Demo Admin',
            'tenant_id' => self::TENANT_ID,
            'property_id' => null,
            'parent_user_id' => null,
            'role' => UserRole::ADMIN->value,
            'is_active' => true,
            'organization_name' => $organization->name,
            'currency' => 'EUR',
        ]);

        $manager = $this->upsertUser('manager@example.com', [
            'name' => 'Demo Manager',
            'tenant_id' => self::TENANT_ID,
            'property_id' => null,
            'parent_user_id' => $admin->id,
            'role' => UserRole::MANAGER->value,
            'is_active' => true,
            'organization_name' => null,
            'currency' => 'EUR',
        ]);

        $subscriptionData = Subscription::factory()
            ->professional()
            ->active()
            ->make([
                'user_id' => $admin->id,
                'plan_type' => SubscriptionPlanType::PROFESSIONAL->value,
                'status' => SubscriptionStatus::ACTIVE->value,
                'starts_at' => now()->startOfMonth(),
                'expires_at' => now()->addYear()->endOfMonth(),
                'max_properties' => 50,
                'max_tenants' => 200,
            ])->getAttributes();

        $subscription = Subscription::query()->updateOrCreate(
            ['user_id' => $admin->id],
            $subscriptionData,
        );

        SubscriptionRenewal::query()->updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'old_expires_at' => $subscription->expires_at,
            ],
            SubscriptionRenewal::factory()->make([
                'subscription_id' => $subscription->id,
                'method' => 'manual',
                'period' => 'annually',
                'old_expires_at' => $subscription->expires_at,
                'new_expires_at' => $subscription->expires_at->copy()->addYear(),
                'duration_days' => 365,
                'user_id' => $admin->id,
                'notes' => 'Seeded renewal baseline',
            ])->getAttributes(),
        );

        return [$organization, $superadmin, $admin, $manager];
    }

    private function seedFoundation(): void
    {
        $this->call(LanguageSeeder::class);

        $eur = Currency::query()->updateOrCreate(
            ['code' => 'EUR'],
            Currency::factory()->eur()->default()->make([
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => true,
            ])->getAttributes(),
        );

        Currency::query()->updateOrCreate(
            ['code' => 'GBP'],
            Currency::factory()->gbp()->active()->make([
                'code' => 'GBP',
                'name' => 'British Pound Sterling',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_active' => true,
                'is_default' => false,
            ])->getAttributes(),
        );

        Currency::query()->where('code', '!=', $eur->code)->update(['is_default' => false]);

        $providerDefinitions = [
            'Ignitis' => [
                'state' => 'ignitis',
                'contact_info' => [
                    'phone' => '+370 700 55 055',
                    'email' => 'info@ignitis.lt',
                    'website' => 'https://www.ignitis.lt',
                ],
            ],
            'Vilniaus Vandenys' => [
                'state' => 'vilniausVandenys',
                'contact_info' => [
                    'phone' => '+370 5 266 2600',
                    'email' => 'info@vv.lt',
                    'website' => 'https://www.vv.lt',
                ],
            ],
            'Vilniaus Energija' => [
                'state' => 'vilniausEnergija',
                'contact_info' => [
                    'phone' => '+370 5 239 5555',
                    'email' => 'info@ve.lt',
                    'website' => 'https://www.ve.lt',
                ],
            ],
        ];

        $providers = [];

        foreach ($providerDefinitions as $name => $definition) {
            $factory = Provider::factory()->{$definition['state']}();
            $providers[$name] = Provider::query()->updateOrCreate(
                ['name' => $name],
                $factory->make([
                    'name' => $name,
                    'contact_info' => $definition['contact_info'],
                ])->getAttributes(),
            );
        }

        Tariff::query()->updateOrCreate(
            ['provider_id' => $providers['Ignitis']->id, 'name' => 'Ignitis Standard Time-of-Use'],
            [
                'provider_id' => $providers['Ignitis']->id,
                'name' => 'Ignitis Standard Time-of-Use',
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                    ],
                    'weekend_logic' => 'apply_night_rate',
                    'fixed_fee' => 0.00,
                ],
                'active_from' => now()->subMonths(6)->startOfDay(),
                'active_until' => null,
            ],
        );

        Tariff::query()->updateOrCreate(
            ['provider_id' => $providers['Vilniaus Vandenys']->id, 'name' => 'VV Standard Water Rates'],
            [
                'provider_id' => $providers['Vilniaus Vandenys']->id,
                'name' => 'VV Standard Water Rates',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'supply_rate' => 0.97,
                    'sewage_rate' => 1.23,
                    'fixed_fee' => 0.85,
                ],
                'active_from' => now()->subMonths(6)->startOfDay(),
                'active_until' => null,
            ],
        );

        Tariff::query()->updateOrCreate(
            ['provider_id' => $providers['Vilniaus Energija']->id, 'name' => 'VE Heating Standard'],
            [
                'provider_id' => $providers['Vilniaus Energija']->id,
                'name' => 'VE Heating Standard',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.065,
                    'fixed_fee' => 0,
                ],
                'active_from' => now()->subMonths(6)->startOfDay(),
                'active_until' => null,
            ],
        );
    }

    /**
     * @return array{tenant_records: array<int, Tenant>}
     */
    private function seedAssets(User $admin, User $manager): array
    {
        $buildingDefinitions = [
            [
                'name' => 'Gedimino Residence',
                'address' => 'Gedimino pr. 25, Vilnius',
                'total_apartments' => 24,
            ],
            [
                'name' => 'Konstitucijos Tower',
                'address' => 'Konstitucijos pr. 12, Vilnius',
                'total_apartments' => 18,
            ],
        ];

        $buildings = [];

        foreach ($buildingDefinitions as $definition) {
            $buildings[] = Building::query()->updateOrCreate(
                ['tenant_id' => self::TENANT_ID, 'address' => $definition['address']],
                Building::factory()->forTenantId(self::TENANT_ID)->make($definition)->getAttributes(),
            );
        }

        $propertyDefinitions = [
            ['building' => 0, 'unit' => 'Apt 1', 'area' => 67.5],
            ['building' => 0, 'unit' => 'Apt 2', 'area' => 71.3],
            ['building' => 1, 'unit' => 'Apt 8', 'area' => 63.1],
            ['building' => 1, 'unit' => 'Apt 12', 'area' => 79.4],
        ];

        $properties = [];

        foreach ($propertyDefinitions as $definition) {
            $building = $buildings[$definition['building']];
            $address = "{$building->address}, {$definition['unit']}";

            $properties[] = Property::query()->updateOrCreate(
                ['tenant_id' => self::TENANT_ID, 'address' => $address],
                Property::factory()->forTenantId(self::TENANT_ID)->apartment()->make([
                    'tenant_id' => self::TENANT_ID,
                    'building_id' => $building->id,
                    'unit_number' => $definition['unit'],
                    'address' => $address,
                    'area_sqm' => $definition['area'],
                ])->getAttributes(),
            );
        }

        $tenantProfiles = [
            [
                'slug' => 'alina-petrauskiene',
                'name' => 'Alina Petrauskienė',
                'email' => 'tenant.alina@example.com',
                'phone' => '+37060010101',
                'property_index' => 0,
                'monthly_rent' => 780.00,
                'deposit' => 1500.00,
            ],
            [
                'slug' => 'marius-jonaitis',
                'name' => 'Marius Jonaitis',
                'email' => 'tenant.marius@example.com',
                'phone' => '+37060020202',
                'property_index' => 2,
                'monthly_rent' => 820.00,
                'deposit' => 1600.00,
            ],
        ];

        $tenantRecords = [];

        foreach ($tenantProfiles as $profile) {
            $property = $properties[$profile['property_index']];

            $tenantUser = $this->upsertUser($profile['email'], [
                'name' => $profile['name'],
                'tenant_id' => self::TENANT_ID,
                'property_id' => $property->id,
                'parent_user_id' => $admin->id,
                'role' => UserRole::TENANT->value,
                'is_active' => true,
                'organization_name' => null,
                'currency' => 'EUR',
            ]);

            $tenantRecordData = Tenant::factory()
                ->forTenantId(self::TENANT_ID)
                ->forProperty($property)
                ->make([
                    'slug' => $profile['slug'],
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                    'phone' => $profile['phone'],
                    'property_id' => $property->id,
                    'lease_start' => now()->subMonths(8)->startOfMonth()->toDateString(),
                    'lease_end' => now()->addMonths(4)->endOfMonth()->toDateString(),
                ])->getAttributes();

            $tenantRecord = Tenant::query()->updateOrCreate(
                ['slug' => $profile['slug']],
                $tenantRecordData,
            );

            DB::table('property_tenant')->updateOrInsert(
                [
                    'property_id' => $property->id,
                    'tenant_id' => $tenantRecord->id,
                ],
                [
                    'assigned_at' => now()->subMonths(8)->startOfMonth(),
                    'vacated_at' => null,
                    'monthly_rent' => $profile['monthly_rent'],
                    'deposit_amount' => $profile['deposit'],
                    'lease_type' => 'standard',
                    'notes' => 'Seeded comprehensive tenant assignment',
                    'assigned_by' => $manager->id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            Lease::query()->updateOrCreate(
                [
                    'property_id' => $property->id,
                    'renter_id' => $tenantRecord->id,
                ],
                Lease::factory()->make([
                    'tenant_id' => self::TENANT_ID,
                    'property_id' => $property->id,
                    'renter_id' => $tenantRecord->id,
                    'start_date' => now()->subMonths(8)->startOfMonth()->toDateString(),
                    'end_date' => now()->addMonths(4)->endOfMonth()->toDateString(),
                    'monthly_rent' => $profile['monthly_rent'],
                    'deposit' => $profile['deposit'],
                    'is_active' => true,
                ])->getAttributes(),
            );

            $tenantRecords[] = $tenantRecord;

            $this->seedPropertyMeters($property);

            // keep user property assignment synchronized
            $tenantUser->forceFill(['property_id' => $property->id])->save();
        }

        return ['tenant_records' => $tenantRecords];
    }

    private function seedPropertyMeters(Property $property): void
    {
        $meterBlueprints = [
            ['suffix' => 'EL', 'type' => MeterType::ELECTRICITY, 'supports_zones' => true],
            ['suffix' => 'WC', 'type' => MeterType::WATER_COLD, 'supports_zones' => false],
            ['suffix' => 'WH', 'type' => MeterType::WATER_HOT, 'supports_zones' => false],
        ];

        foreach ($meterBlueprints as $index => $blueprint) {
            $serialNumber = sprintf(
                'RC-%s-%d-%03d',
                $blueprint['suffix'],
                self::TENANT_ID,
                ($property->id * 10) + $index + 1,
            );

            Meter::query()->updateOrCreate(
                ['serial_number' => $serialNumber],
                Meter::factory()->forProperty($property)->make([
                    'tenant_id' => self::TENANT_ID,
                    'property_id' => $property->id,
                    'serial_number' => $serialNumber,
                    'type' => $blueprint['type'],
                    'supports_zones' => $blueprint['supports_zones'],
                    'installation_date' => now()->subYear()->toDateString(),
                ])->getAttributes(),
            );
        }
    }

    /**
     * @param  array<int, Tenant>  $tenantRecords
     * @return array{invoices: array<int, Invoice>}
     */
    private function seedBilling(User $manager, array $tenantRecords): array
    {
        $invoices = [];

        foreach ($tenantRecords as $tenantRecord) {
            for ($offset = 1; $offset <= 2; $offset++) {
                $periodStart = now()->startOfMonth()->subMonths($offset);
                $periodEnd = $periodStart->copy()->endOfMonth();

                $invoice = Invoice::query()->firstOrCreate(
                    [
                        'tenant_renter_id' => $tenantRecord->id,
                        'billing_period_start' => $periodStart->copy()->startOfDay()->toDateTimeString(),
                        'billing_period_end' => $periodEnd->copy()->endOfDay()->toDateTimeString(),
                    ],
                    Invoice::factory()->forTenantRenter($tenantRecord)->make([
                        'tenant_id' => self::TENANT_ID,
                        'tenant_renter_id' => $tenantRecord->id,
                        'invoice_number' => sprintf('RC-%s-%04d', $periodStart->format('Ym'), $tenantRecord->id),
                        'billing_period_start' => $periodStart->copy()->startOfDay()->toDateTimeString(),
                        'billing_period_end' => $periodEnd->copy()->endOfDay()->toDateTimeString(),
                        'due_date' => $periodEnd->copy()->addDays(14)->toDateString(),
                        'status' => $offset === 1 ? InvoiceStatus::DRAFT->value : InvoiceStatus::FINALIZED->value,
                        'finalized_at' => $offset === 1 ? null : $periodEnd->copy()->endOfDay()->toDateTimeString(),
                        'generated_by' => $manager->id,
                        'generated_at' => now()->toDateTimeString(),
                    ])->getAttributes(),
                );

                $this->seedInvoiceItems($invoice);
                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'total_amount' => (float) $invoice->items()->sum('total'),
                        'updated_at' => now(),
                    ]);

                $invoices[] = $invoice;
            }
        }

        $meters = Meter::query()->where('tenant_id', self::TENANT_ID)->get();

        foreach ($meters as $meter) {
            $baseValue = (float) (1000 + ($meter->id * 25));

            for ($offset = 0; $offset < 3; $offset++) {
                $date = now()->startOfMonth()->subMonths($offset)->endOfMonth()->setTime(12, 0);
                $value = $baseValue + (($offset + 1) * 45.5);

                MeterReading::query()->firstOrCreate(
                    [
                        'meter_id' => $meter->id,
                        'reading_date' => $date,
                        'zone' => null,
                    ],
                    MeterReading::factory()->forMeter($meter)->make([
                        'tenant_id' => self::TENANT_ID,
                        'meter_id' => $meter->id,
                        'reading_date' => $date,
                        'value' => $value,
                        'zone' => null,
                        'entered_by' => $manager->id,
                    ])->getAttributes(),
                );
            }
        }

        return ['invoices' => $invoices];
    }

    private function seedInvoiceItems(Invoice $invoice): void
    {
        $itemBlueprints = [
            ['description' => 'Electricity consumption', 'quantity' => 125.40, 'unit' => 'kWh', 'unit_price' => 0.18],
            ['description' => 'Water supply', 'quantity' => 18.20, 'unit' => 'm³', 'unit_price' => 0.97],
            ['description' => 'Heating', 'quantity' => 310.00, 'unit' => 'kWh', 'unit_price' => 0.065],
        ];

        foreach ($itemBlueprints as $blueprint) {
            $total = round($blueprint['quantity'] * $blueprint['unit_price'], 2);

            InvoiceItem::query()->updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'description' => $blueprint['description'],
                ],
                InvoiceItem::factory()->forInvoice($invoice)->make([
                    'invoice_id' => $invoice->id,
                    'description' => $blueprint['description'],
                    'quantity' => $blueprint['quantity'],
                    'unit' => $blueprint['unit'],
                    'unit_price' => $blueprint['unit_price'],
                    'total' => $total,
                ])->getAttributes(),
            );
        }
    }

    /**
     * @param  array<int, Tenant>  $tenantRecords
     * @param  array<int, Invoice>  $invoices
     */
    private function seedAuxiliary(
        Organization $organization,
        User $superadmin,
        User $admin,
        User $manager,
        array $tenantRecords,
        array $invoices,
    ): void {
        foreach ([
            ['name' => 'Urgent', 'color' => '#ef4444'],
            ['name' => 'Maintenance', 'color' => '#0ea5e9'],
            ['name' => 'Billing', 'color' => '#22c55e'],
        ] as $tag) {
            Tag::query()->updateOrCreate(
                ['tenant_id' => self::TENANT_ID, 'slug' => Str::slug($tag['name'])],
                Tag::factory()->forTenant(self::TENANT_ID)->make([
                    'tenant_id' => self::TENANT_ID,
                    'name' => $tag['name'],
                    'slug' => Str::slug($tag['name']),
                    'color' => $tag['color'],
                    'description' => "{$tag['name']} seeded tag",
                ])->getAttributes(),
            );
        }

        Translation::query()->updateOrCreate(
            ['group' => 'seeding', 'key' => 'invoice.summary.title'],
            Translation::factory()->make([
                'group' => 'seeding',
                'key' => 'invoice.summary.title',
                'values' => [
                    'en' => 'Invoice Summary',
                    'lt' => 'Sąskaitos suvestinė',
                    'ru' => 'Сводка счета',
                ],
            ])->getAttributes(),
        );

        $task = EnhancedTask::query()->updateOrCreate(
            ['title' => 'Check monthly meter consistency', 'created_by' => $manager->id],
            EnhancedTask::factory()->make([
                'tenant_id' => $manager->id,
                'created_by' => $manager->id,
                'property_id' => $tenantRecords[0]->property_id,
                'title' => 'Check monthly meter consistency',
                'description' => 'Verify latest readings before final invoice approval.',
                'type' => 'inspection',
                'status' => 'in_progress',
                'priority' => 'high',
            ])->getAttributes(),
        );

        Comment::query()->firstOrCreate(
            [
                'commentable_type' => EnhancedTask::class,
                'commentable_id' => $task->id,
                'user_id' => $manager->id,
            ],
            Comment::factory()->forCommentable($task)->make([
                'tenant_id' => self::TENANT_ID,
                'commentable_type' => EnhancedTask::class,
                'commentable_id' => $task->id,
                'user_id' => $manager->id,
                'body' => 'Seeded task comment: meter data looks consistent.',
                'is_internal' => true,
            ])->getAttributes(),
        );

        AuditLog::query()->updateOrCreate(
            [
                'tenant_id' => self::TENANT_ID,
                'event' => 'updated',
                'auditable_type' => Organization::class,
                'auditable_id' => $organization->id,
            ],
            AuditLog::factory()->forTenantId(self::TENANT_ID)->make([
                'tenant_id' => self::TENANT_ID,
                'user_id' => $admin->id,
                'auditable_type' => Organization::class,
                'auditable_id' => $organization->id,
                'event' => 'updated',
                'old_values' => ['plan' => 'basic'],
                'new_values' => ['plan' => 'professional'],
            ])->getAttributes(),
        );

        Activity::query()->firstOrCreate(
            [
                'causer_type' => User::class,
                'causer_id' => $admin->id,
                'subject_type' => Organization::class,
                'subject_id' => $organization->id,
                'event' => 'updated',
            ],
            Activity::factory()->forCauser($admin)->make([
                'tenant_id' => $admin->id,
                'causer_type' => User::class,
                'causer_id' => $admin->id,
                'subject_type' => Organization::class,
                'subject_id' => $organization->id,
                'description' => 'Organization plan updated by seeded admin.',
                'event' => 'updated',
            ])->getAttributes(),
        );

        OrganizationActivityLog::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'action' => 'seeded_comprehensive_dataset',
            ],
            OrganizationActivityLog::factory()->make([
                'organization_id' => $organization->id,
                'user_id' => $admin->id,
                'action' => 'seeded_comprehensive_dataset',
                'resource_type' => 'Seeder',
                'resource_id' => $organization->id,
                'metadata' => ['seed' => self::class],
            ])->getAttributes(),
        );

        SystemConfiguration::query()->updateOrCreate(
            ['key' => 'billing.default_currency'],
            SystemConfiguration::factory()->make([
                'key' => 'billing.default_currency',
                'value' => ['value' => 'EUR'],
                'type' => 'string',
                'description' => 'Default billing currency used by seeded data',
                'is_tenant_configurable' => true,
                'requires_restart' => false,
                'updated_by_admin_id' => $superadmin->id,
            ])->getAttributes(),
        );

        SystemHealthMetric::query()->updateOrCreate(
            ['metric_type' => 'database', 'metric_name' => 'connection_status'],
            SystemHealthMetric::factory()->healthy()->database()->make([
                'metric_type' => 'database',
                'metric_name' => 'connection_status',
                'status' => 'healthy',
                'checked_at' => now(),
            ])->getAttributes(),
        );

        IntegrationHealthCheck::query()->updateOrCreate(
            ['service_name' => 'billing-api', 'endpoint' => 'https://api.example.test/billing/health'],
            IntegrationHealthCheck::factory()->make([
                'service_name' => 'billing-api',
                'endpoint' => 'https://api.example.test/billing/health',
                'status' => 'healthy',
                'response_time_ms' => 220,
                'checked_at' => now(),
            ])->getAttributes(),
        );

        SecurityViolation::query()->updateOrCreate(
            [
                'tenant_id' => $admin->id,
                'violation_type' => 'csp',
                'policy_directive' => 'script-src',
                'document_uri' => 'https://rentcounter.test/admin/dashboard',
            ],
            SecurityViolation::factory()->suspicious()->make([
                'tenant_id' => $admin->id,
                'violation_type' => 'csp',
                'policy_directive' => 'script-src',
                'document_uri' => 'https://rentcounter.test/admin/dashboard',
                'blocked_uri' => 'http://suspicious.example.com/script.js',
            ])->getAttributes(),
        );

        $notification = PlatformNotification::query()->updateOrCreate(
            ['title' => 'Scheduled maintenance for billing recalculation'],
            PlatformNotification::factory()->make([
                'title' => 'Scheduled maintenance for billing recalculation',
                'message' => 'Billing recalculation will run tonight at 23:00.',
                'target_type' => 'organization',
                'target_criteria' => [$organization->id],
                'status' => 'sent',
                'scheduled_at' => now()->subDay(),
                'sent_at' => now()->subDay()->addHour(),
                'created_by' => $superadmin->id,
                'delivery_stats' => ['sent' => 1, 'read' => 1],
            ])->getAttributes(),
        );

        PlatformNotificationRecipient::query()->updateOrCreate(
            [
                'platform_notification_id' => $notification->id,
                'organization_id' => $organization->id,
            ],
            PlatformNotificationRecipient::factory()->make([
                'platform_notification_id' => $notification->id,
                'organization_id' => $organization->id,
                'email' => $organization->email,
                'delivery_status' => 'read',
                'sent_at' => now()->subDay()->addHour(),
                'read_at' => now()->subDay()->addHours(2),
            ])->getAttributes(),
        );

        if (! empty($invoices)) {
            $invoice = $invoices[0];

            Activity::query()->firstOrCreate(
                [
                    'causer_type' => User::class,
                    'causer_id' => $manager->id,
                    'subject_type' => Invoice::class,
                    'subject_id' => $invoice->id,
                    'event' => 'created',
                ],
                Activity::factory()->forCauser($manager)->make([
                    'tenant_id' => $manager->id,
                    'causer_type' => User::class,
                    'causer_id' => $manager->id,
                    'subject_type' => Invoice::class,
                    'subject_id' => $invoice->id,
                    'description' => 'Invoice created by comprehensive seed flow.',
                    'event' => 'created',
                ])->getAttributes(),
            );
        }
    }

    private function upsertUser(string $email, array $attributes): User
    {
        $user = User::query()->withoutGlobalScopes()->firstOrNew(['email' => $email]);

        $user->forceFill(array_merge([
            'name' => $attributes['name'] ?? $email,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'role' => UserRole::TENANT->value,
            'tenant_id' => null,
            'property_id' => null,
            'parent_user_id' => null,
            'is_active' => true,
            'organization_name' => null,
            'currency' => 'EUR',
            'last_login_at' => null,
            'is_super_admin' => ($attributes['role'] ?? null) === UserRole::SUPERADMIN->value,
            'tenant_permissions' => null,
            'tenant_joined_at' => null,
            'is_tenant_admin' => false,
            'system_tenant_id' => null,
            'suspended_at' => null,
            'suspension_reason' => null,
        ], $attributes));

        $user->save();

        return $user->refresh();
    }
}
