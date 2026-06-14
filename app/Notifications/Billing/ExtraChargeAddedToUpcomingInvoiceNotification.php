<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\ExtraCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ExtraChargeAddedToUpcomingInvoiceNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ExtraCharge $charge,
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
            'title' => __('admin.extra_charges.notifications.tenant_charge_added_title'),
            'body' => __('admin.extra_charges.notifications.tenant_charge_added_body', [
                'charge' => $this->charge->title,
            ]),
            'url' => route('filament.admin.pages.tenant-invoice-history', [], false),
            'extra_charge_id' => $this->charge->id,
            'organization_id' => $this->charge->organization_id,
            'property_id' => $this->charge->property_id,
            'billing_period_id' => $this->charge->billing_period_id,
        ];
    }
}
