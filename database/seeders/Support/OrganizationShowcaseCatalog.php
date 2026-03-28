<?php

namespace Database\Seeders\Support;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;

final class OrganizationShowcaseCatalog
{
    /**
     * @return array<int, array{
     *     slug: string,
     *     name: string,
     *     short_name: string,
     *     street: string,
     *     admin_name: string,
     *     manager_name: string,
     *     tenant_prefix: string,
     *     organization_factory_state: string,
     *     plan: SubscriptionPlan,
     *     status: OrganizationStatus,
     *     subscription_status: SubscriptionStatus,
     *     is_trial: bool,
     *     volumes: array{
     *         buildings: int,
     *         tenants: int,
     *         properties: int,
     *         meters_per_property: int,
     *         invoices_per_property: int,
     *         tasks: int
     *     }
     * }}
     */
    public static function blueprints(): array
    {
        return [
            [
                'slug' => 'demo-baltic-starter',
                'name' => 'Vilnius Riverside Homes',
                'short_name' => 'Vilnius Riverside',
                'street' => 'Neries Quay',
                'admin_name' => 'Austeja Petrauskaite',
                'manager_name' => 'Mantas Vaitkus',
                'tenant_prefix' => 'Vilnius',
                'organization_factory_state' => 'starterShowcase',
                'plan' => SubscriptionPlan::STARTER,
                'status' => OrganizationStatus::ACTIVE,
                'subscription_status' => SubscriptionStatus::TRIALING,
                'is_trial' => true,
                'volumes' => [
                    'buildings' => 3,
                    'tenants' => 8,
                    'properties' => 8,
                    'meters_per_property' => 2,
                    'invoices_per_property' => 3,
                    'tasks' => 2,
                ],
            ],
            [
                'slug' => 'demo-baltic-basic',
                'name' => 'Kaunas Central Lofts',
                'short_name' => 'Kaunas Central',
                'street' => 'Laisves Avenue',
                'admin_name' => 'Monika Jankauskaite',
                'manager_name' => 'Tadas Kazlauskas',
                'tenant_prefix' => 'Kaunas',
                'organization_factory_state' => 'basicShowcase',
                'plan' => SubscriptionPlan::BASIC,
                'status' => OrganizationStatus::ACTIVE,
                'subscription_status' => SubscriptionStatus::ACTIVE,
                'is_trial' => false,
                'volumes' => [
                    'buildings' => 3,
                    'tenants' => 8,
                    'properties' => 8,
                    'meters_per_property' => 2,
                    'invoices_per_property' => 3,
                    'tasks' => 2,
                ],
            ],
            [
                'slug' => 'demo-baltic-professional',
                'name' => 'Riga Old Town Suites',
                'short_name' => 'Riga Old Town',
                'street' => 'Valnu Street',
                'admin_name' => 'Elina Ozola',
                'manager_name' => 'Janis Berzins',
                'tenant_prefix' => 'Riga',
                'organization_factory_state' => 'professionalShowcase',
                'plan' => SubscriptionPlan::PROFESSIONAL,
                'status' => OrganizationStatus::ACTIVE,
                'subscription_status' => SubscriptionStatus::ACTIVE,
                'is_trial' => false,
                'volumes' => [
                    'buildings' => 3,
                    'tenants' => 8,
                    'properties' => 8,
                    'meters_per_property' => 2,
                    'invoices_per_property' => 3,
                    'tasks' => 2,
                ],
            ],
            [
                'slug' => 'demo-baltic-enterprise',
                'name' => 'Tallinn Harbor Offices',
                'short_name' => 'Tallinn Harbor',
                'street' => 'Sadama Street',
                'admin_name' => 'Mari Tamm',
                'manager_name' => 'Rasmus Saar',
                'tenant_prefix' => 'Tallinn',
                'organization_factory_state' => 'enterpriseShowcase',
                'plan' => SubscriptionPlan::ENTERPRISE,
                'status' => OrganizationStatus::ACTIVE,
                'subscription_status' => SubscriptionStatus::ACTIVE,
                'is_trial' => false,
                'volumes' => [
                    'buildings' => 3,
                    'tenants' => 8,
                    'properties' => 8,
                    'meters_per_property' => 2,
                    'invoices_per_property' => 3,
                    'tasks' => 2,
                ],
            ],
            [
                'slug' => 'demo-baltic-custom',
                'name' => 'Klaipeda Port Residences',
                'short_name' => 'Klaipeda Port',
                'street' => 'Danes Street',
                'admin_name' => 'Greta Mockute',
                'manager_name' => 'Rokas Butkus',
                'tenant_prefix' => 'Klaipeda',
                'organization_factory_state' => 'customShowcase',
                'plan' => SubscriptionPlan::CUSTOM,
                'status' => OrganizationStatus::ACTIVE,
                'subscription_status' => SubscriptionStatus::ACTIVE,
                'is_trial' => false,
                'volumes' => [
                    'buildings' => 3,
                    'tenants' => 8,
                    'properties' => 8,
                    'meters_per_property' => 2,
                    'invoices_per_property' => 3,
                    'tasks' => 2,
                ],
            ],
        ];
    }
}
