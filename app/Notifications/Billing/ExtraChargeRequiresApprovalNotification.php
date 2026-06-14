<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\ExtraCharge;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ExtraChargeRequiresApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ExtraCharge $charge,
        private readonly User $manager,
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
            'title' => __('admin.extra_charges.notifications.approval_required_title'),
            'body' => __('admin.extra_charges.notifications.approval_required_body', [
                'manager' => $this->manager->name,
                'charge' => $this->charge->title,
            ]),
            'url' => route('filament.admin.resources.extra-charges.pending-approvals', [], false),
            'extra_charge_id' => $this->charge->id,
            'organization_id' => $this->charge->organization_id,
            'tenant_id' => $this->charge->tenant_id,
            'property_id' => $this->charge->property_id,
            'manager_id' => $this->manager->id,
        ];
    }
}
