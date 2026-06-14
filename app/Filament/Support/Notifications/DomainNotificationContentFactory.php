<?php

declare(strict_types=1);

namespace App\Filament\Support\Notifications;

use App\Models\ExtraCharge;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\MeterReading;
use App\Models\OrganizationInvitation;
use App\Models\RentalContract;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

final class DomainNotificationContentFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function make(string $type, Model $subject, array $data = []): DomainNotificationContent
    {
        $context = $this->context($subject, $data);
        $actionUrl = $this->stringValue($data['action_url'] ?? null) ?? $this->actionUrl($type, $subject);
        $dedupeKey = $this->stringValue($data['dedupe_key'] ?? null) ?? $this->dedupeKey($type, $subject, $data);

        return new DomainNotificationContent(
            type: $type,
            title: __("notifications.domain.{$type}.title", $context),
            message: __("notifications.domain.{$type}.message", $context),
            actionUrl: $actionUrl,
            data: [
                ...$data,
                ...$this->subjectData($subject),
            ],
            dedupeKey: $dedupeKey,
            sendEmail: (bool) ($data['send_email'] ?? DomainNotificationCatalog::shouldEmail($type)),
            emailSubject: __("notifications.domain.{$type}.subject", $context),
            emailGreeting: __('notifications.mail.greeting'),
            emailActionLabel: __("notifications.domain.{$type}.action", $context),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, scalar|null>
     */
    private function context(Model $subject, array $data): array
    {
        $context = [
            'number' => $this->subjectNumber($subject),
            'name' => $this->subjectName($subject),
            'email' => $this->subjectEmail($subject),
            'days' => $data['days'] ?? $data['milestone'] ?? null,
            'date' => $this->dateLabel($data['date'] ?? null),
            'amount' => $data['amount'] ?? null,
        ];

        if ($subject instanceof Invoice) {
            return [
                ...$context,
                'number' => $subject->invoice_number,
                'due_date' => $this->dateLabel($subject->due_date),
                'period_start' => $this->dateLabel($subject->billing_period_start),
                'period_end' => $this->dateLabel($subject->billing_period_end),
            ];
        }

        if ($subject instanceof MeterReading) {
            $subject->loadMissing(['meter:id,name,identifier', 'property:id,name,unit_number']);

            return [
                ...$context,
                'number' => (string) $subject->getKey(),
                'name' => (string) ($subject->meter?->name ?? $subject->meter?->identifier ?? $subject->getKey()),
                'property' => (string) ($subject->property?->name ?? __('dashboard.not_available')),
                'date' => $this->dateLabel($subject->reading_date),
            ];
        }

        if ($subject instanceof OrganizationInvitation) {
            $subject->loadMissing(['organization:id,name', 'tenant:id,name,email']);

            return [
                ...$context,
                'name' => (string) ($subject->full_name ?: $subject->tenant?->name ?: $subject->email),
                'email' => $subject->email,
                'organization' => (string) ($subject->organization?->name ?? __('dashboard.not_available')),
                'date' => $this->dateLabel($subject->expires_at),
            ];
        }

        if ($subject instanceof RentalContract) {
            return [
                ...$context,
                'number' => $subject->contract_number,
                'date' => $this->dateLabel($subject->end_date),
            ];
        }

        if ($subject instanceof ServiceConfiguration) {
            return [
                ...$context,
                'name' => (string) ($subject->service_name ?? $subject->getKey()),
            ];
        }

        if ($subject instanceof InvoicePayment) {
            $subject->loadMissing('invoice:id,invoice_number');

            return [
                ...$context,
                'number' => (string) ($subject->invoice?->invoice_number ?? $subject->invoice_id),
                'amount' => (string) $subject->amount,
            ];
        }

        return $context;
    }

    private function actionUrl(string $type, Model $subject): ?string
    {
        if ($subject instanceof Invoice) {
            return match ($type) {
                DomainNotificationCatalog::READING_REQUIRED,
                DomainNotificationCatalog::READING_REMINDER,
                DomainNotificationCatalog::READING_REJECTED => $this->route('tenant.readings.create', ['invoice' => $subject->id]),
                DomainNotificationCatalog::INVOICE_SENT,
                DomainNotificationCatalog::INVOICE_OVERDUE,
                DomainNotificationCatalog::PAYMENT_RECEIVED => $this->route('tenant.invoices.index').'#tenant-invoice-'.$subject->id,
                default => $this->route('filament.admin.resources.invoices.edit', ['record' => $subject]),
            };
        }

        if ($subject instanceof MeterReading) {
            return match ($type) {
                DomainNotificationCatalog::READING_REJECTED => $this->route('tenant.readings.create'),
                default => $this->route('filament.admin.resources.meter-readings.view', ['record' => $subject]),
            };
        }

        if ($subject instanceof OrganizationInvitation) {
            if ($type === DomainNotificationCatalog::TENANT_INVITATION_SENT && filled($subject->routeToken())) {
                return $this->route('invitation.show', ['token' => $subject->routeToken()]);
            }

            return $subject->role?->value === 'tenant'
                ? $this->route('filament.admin.resources.tenants.index')
                : $this->route('filament.admin.resources.organization-users.index');
        }

        if ($subject instanceof RentalContract) {
            return $this->route('filament.admin.resources.tenants.view', ['record' => $subject->tenant_id]);
        }

        if ($subject instanceof ServiceConfiguration) {
            return $this->route('filament.admin.resources.service-configurations.edit', ['record' => $subject]);
        }

        if ($subject instanceof ExtraCharge) {
            return $this->route('filament.admin.resources.invoice-items.index');
        }

        return null;
    }

    private function route(string $name, array $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        return route($name, $parameters, false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dedupeKey(string $type, Model $subject, array $data): string
    {
        $milestone = $this->stringValue($data['milestone'] ?? $data['days'] ?? null);

        return collect([
            $type,
            $subject::class,
            (string) $subject->getKey(),
            $milestone,
        ])->filter()->implode(':');
    }

    /**
     * @return array<string, mixed>
     */
    private function subjectData(Model $subject): array
    {
        return match (true) {
            $subject instanceof Invoice => [
                'invoice_id' => $subject->id,
                'invoice_number' => $subject->invoice_number,
                'organization_id' => $subject->organization_id,
                'property_id' => $subject->property_id,
                'tenant_user_id' => $subject->tenant_user_id,
            ],
            $subject instanceof MeterReading => [
                'meter_reading_id' => $subject->id,
                'organization_id' => $subject->organization_id,
                'property_id' => $subject->property_id,
                'meter_id' => $subject->meter_id,
                'submitted_by_user_id' => $subject->submitted_by_user_id,
            ],
            $subject instanceof OrganizationInvitation => [
                'organization_invitation_id' => $subject->id,
                'organization_id' => $subject->organization_id,
                'tenant_user_id' => $subject->tenant_id,
                'email' => $subject->email,
                'role' => $subject->role?->value,
            ],
            $subject instanceof RentalContract => [
                'rental_contract_id' => $subject->id,
                'organization_id' => $subject->organization_id,
                'property_id' => $subject->property_id,
                'tenant_user_id' => $subject->tenant_id,
            ],
            $subject instanceof ServiceConfiguration => [
                'service_configuration_id' => $subject->id,
                'organization_id' => $subject->organization_id,
                'property_id' => $subject->property_id,
            ],
            $subject instanceof InvoicePayment => [
                'invoice_payment_id' => $subject->id,
                'invoice_id' => $subject->invoice_id,
                'organization_id' => $subject->organization_id,
            ],
            default => [
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
            ],
        };
    }

    private function subjectNumber(Model $subject): string
    {
        return match (true) {
            $subject instanceof Invoice => (string) $subject->invoice_number,
            $subject instanceof RentalContract => (string) $subject->contract_number,
            default => (string) $subject->getKey(),
        };
    }

    private function subjectName(Model $subject): string
    {
        return match (true) {
            $subject instanceof User => $subject->name,
            $subject instanceof ServiceConfiguration => (string) $subject->service_name,
            default => $this->subjectNumber($subject),
        };
    }

    private function subjectEmail(Model $subject): ?string
    {
        return $subject instanceof User || $subject instanceof OrganizationInvitation
            ? (string) $subject->email
            : null;
    }

    private function dateLabel(mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return filled($value) ? (string) $value : __('dashboard.not_available');
    }

    private function stringValue(mixed $value): ?string
    {
        return is_scalar($value) && filled((string) $value) ? (string) $value : null;
    }
}
