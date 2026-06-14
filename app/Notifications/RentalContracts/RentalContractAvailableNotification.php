<?php

declare(strict_types=1);

namespace App\Notifications\RentalContracts;

use App\Models\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class RentalContractAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly RentalContract $rentalContract,
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
            'title' => __('notifications.rental_contracts.available_title'),
            'body' => __('notifications.rental_contracts.available_body', [
                'number' => $this->rentalContract->contract_number,
            ]),
            'url' => route('filament.admin.pages.tenant-documents', [], false).'#rental-contract-'.$this->rentalContract->id,
            'rental_contract_id' => $this->rentalContract->id,
            'organization_id' => $this->rentalContract->organization_id,
            'property_id' => $this->rentalContract->property_id,
        ];
    }
}
