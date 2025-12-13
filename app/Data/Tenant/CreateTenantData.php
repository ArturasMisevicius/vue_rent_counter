<?php

declare(strict_types=1);

namespace App\Data\Tenant;

use App\Enums\SubscriptionPlan;

final readonly class CreateTenantData
{
    public function __construct(
        public string $name,
        public string $email,
        public SubscriptionPlan $subscriptionPlan,
        public ?string $domain = null,
        public ?string $phone = null,
        public array $resourceQuotas = [],
        public array $billingInfo = [],
        public array $settings = [],
        public array $features = [],
        public string $timezone = 'Europe/Vilnius',
        public string $locale = 'lt',
        public string $currency = 'EUR',
        public int $createdByAdminId = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            subscriptionPlan: SubscriptionPlan::from($data['subscription_plan']),
            domain: $data['domain'] ?? null,
            phone: $data['phone'] ?? null,
            resourceQuotas: $data['resource_quotas'] ?? [],
            billingInfo: $data['billing_info'] ?? [],
            settings: $data['settings'] ?? [],
            features: $data['features'] ?? [],
            timezone: $data['timezone'] ?? 'Europe/Vilnius',
            locale: $data['locale'] ?? 'lt',
            currency: $data['currency'] ?? 'EUR',
            createdByAdminId: $data['created_by_admin_id'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'primary_contact_email' => $this->email,
            'plan' => $this->subscriptionPlan,
            'domain' => $this->domain,
            'phone' => $this->phone,
            'resource_quotas' => $this->resourceQuotas,
            'billing_info' => $this->billingInfo,
            'settings' => $this->settings,
            'features' => $this->features,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'created_by_admin_id' => $this->createdByAdminId,
            'is_active' => true,
        ];
    }
}