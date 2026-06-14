<?php

declare(strict_types=1);

namespace App\Filament\Support\Notifications;

final class DomainNotificationCatalog
{
    /**
     * @var list<string>
     */
    public const TYPES = [
        self::INVOICE_CREATED,
        self::READING_REQUIRED,
        self::READING_REMINDER,
        self::READING_SUBMITTED,
        self::READING_APPROVED,
        self::READING_REJECTED,
        self::INVOICE_READY_FOR_REVIEW,
        self::INVOICE_APPROVED,
        self::INVOICE_SENT,
        self::INVOICE_OVERDUE,
        self::PAYMENT_RECEIVED,
        self::TENANT_INVITATION_SENT,
        self::TENANT_INVITATION_ACCEPTED,
        self::TENANT_INVITATION_EXPIRED,
        self::MANAGER_INVITATION_ACCEPTED,
        self::CONTRACT_EXPIRING,
        self::CONTRACT_EXPIRED,
        self::SERVICE_CONFIGURATION_ERROR,
        self::EXTRA_CHARGE_REQUIRES_APPROVAL,
    ];

    public const INVOICE_CREATED = 'invoice_created';

    public const READING_REQUIRED = 'reading_required';

    public const READING_REMINDER = 'reading_reminder';

    public const READING_SUBMITTED = 'reading_submitted';

    public const READING_APPROVED = 'reading_approved';

    public const READING_REJECTED = 'reading_rejected';

    public const INVOICE_READY_FOR_REVIEW = 'invoice_ready_for_review';

    public const INVOICE_APPROVED = 'invoice_approved';

    public const INVOICE_SENT = 'invoice_sent';

    public const INVOICE_OVERDUE = 'invoice_overdue';

    public const PAYMENT_RECEIVED = 'payment_received';

    public const TENANT_INVITATION_SENT = 'tenant_invitation_sent';

    public const TENANT_INVITATION_ACCEPTED = 'tenant_invitation_accepted';

    public const TENANT_INVITATION_EXPIRED = 'tenant_invitation_expired';

    public const MANAGER_INVITATION_ACCEPTED = 'manager_invitation_accepted';

    public const CONTRACT_EXPIRING = 'contract_expiring';

    public const CONTRACT_EXPIRED = 'contract_expired';

    public const SERVICE_CONFIGURATION_ERROR = 'service_configuration_error';

    public const EXTRA_CHARGE_REQUIRES_APPROVAL = 'extra_charge_requires_approval';

    /**
     * @var list<string>
     */
    private const EMAIL_TYPES = [
        self::INVOICE_CREATED,
        self::READING_REQUIRED,
        self::READING_REMINDER,
        self::READING_REJECTED,
        self::INVOICE_SENT,
        self::INVOICE_OVERDUE,
        self::TENANT_INVITATION_SENT,
        self::TENANT_INVITATION_ACCEPTED,
        self::TENANT_INVITATION_EXPIRED,
        self::MANAGER_INVITATION_ACCEPTED,
        self::CONTRACT_EXPIRING,
        self::SERVICE_CONFIGURATION_ERROR,
        self::EXTRA_CHARGE_REQUIRES_APPROVAL,
    ];

    public static function isSupported(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public static function shouldEmail(string $type): bool
    {
        return in_array($type, self::EMAIL_TYPES, true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::TYPES)
            ->mapWithKeys(fn (string $type): array => [$type => __("notifications.types.{$type}")])
            ->all();
    }
}
