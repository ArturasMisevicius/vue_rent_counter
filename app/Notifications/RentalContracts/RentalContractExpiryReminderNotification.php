<?php

declare(strict_types=1);

namespace App\Notifications\RentalContracts;

use App\Models\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class RentalContractExpiryReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly RentalContract $rentalContract,
        private readonly int $daysUntilExpiry,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.rental_contracts.expiry_reminder_title'),
            'body' => __('notifications.rental_contracts.expiry_reminder_body', [
                'number' => $this->rentalContract->contract_number,
                'days' => $this->daysUntilExpiry,
            ]),
            'url' => route('filament.admin.resources.tenants.view', $this->rentalContract->tenant_id, false),
            'rental_contract_id' => $this->rentalContract->id,
            'organization_id' => $this->rentalContract->organization_id,
            'property_id' => $this->rentalContract->property_id,
            'days_until_expiry' => $this->daysUntilExpiry,
        ];
    }
}
