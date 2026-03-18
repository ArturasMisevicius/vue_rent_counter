<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;

final class NotificationPreferenceService
{
    public const NEW_INVOICE_GENERATED = 'new_invoice_generated';

    public const INVOICE_OVERDUE = 'invoice_overdue';

    public const TENANT_SUBMITS_READING = 'tenant_submits_reading';

    public const SUBSCRIPTION_EXPIRING = 'subscription_expiring';

    /**
     * @return array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    public static function defaults(): array
    {
        return [
            self::NEW_INVOICE_GENERATED => false,
            self::INVOICE_OVERDUE => false,
            self::TENANT_SUBMITS_READING => false,
            self::SUBSCRIPTION_EXPIRING => false,
        ];
    }

    /**
     * @return array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    public function resolveForUser(?User $user): array
    {
        if ($user?->organization_id === null) {
            return self::defaults();
        }

        return $this->resolveForOrganizationId($user->organization_id);
    }

    /**
     * @return array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    public function resolveForOrganization(?Organization $organization): array
    {
        return $this->resolveForOrganizationId($organization?->id);
    }

    public function enabledForUser(?User $user, string $preference): bool
    {
        return (bool) ($this->resolveForUser($user)[$preference] ?? false);
    }

    public function enabledForOrganization(?Organization $organization, string $preference): bool
    {
        return (bool) ($this->resolveForOrganization($organization)[$preference] ?? false);
    }

    /**
     * @return array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    private function resolveForOrganizationId(?int $organizationId): array
    {
        if ($organizationId === null) {
            return self::defaults();
        }

        $preferences = OrganizationSetting::query()
            ->select(['id', 'organization_id', 'notification_preferences'])
            ->where('organization_id', $organizationId)
            ->value('notification_preferences');

        return $this->normalize(is_array($preferences) ? $preferences : []);
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    private function normalize(array $preferences): array
    {
        $normalized = array_replace(self::defaults(), [
            self::NEW_INVOICE_GENERATED => (bool) ($preferences[self::NEW_INVOICE_GENERATED] ?? false),
            self::INVOICE_OVERDUE => (bool) ($preferences[self::INVOICE_OVERDUE] ?? false),
            self::TENANT_SUBMITS_READING => (bool) ($preferences[self::TENANT_SUBMITS_READING] ?? false),
            self::SUBSCRIPTION_EXPIRING => (bool) ($preferences[self::SUBSCRIPTION_EXPIRING] ?? false),
        ]);

        if (array_key_exists('invoice_reminders', $preferences) && ! array_key_exists(self::INVOICE_OVERDUE, $preferences)) {
            $normalized[self::INVOICE_OVERDUE] = (bool) $preferences['invoice_reminders'];
        }

        if (array_key_exists('reading_deadline_alerts', $preferences) && ! array_key_exists(self::TENANT_SUBMITS_READING, $preferences)) {
            $normalized[self::TENANT_SUBMITS_READING] = (bool) $preferences['reading_deadline_alerts'];
        }

        return $normalized;
    }
}
