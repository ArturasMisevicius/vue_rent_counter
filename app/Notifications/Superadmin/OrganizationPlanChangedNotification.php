<?php

declare(strict_types=1);

namespace App\Notifications\Superadmin;

use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class OrganizationPlanChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Organization $organization,
        public readonly SubscriptionPlan $oldPlan,
        public readonly SubscriptionPlan $newPlan,
        public readonly string $reason,
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
            'title' => __('superadmin.organizations.notifications.plan_changed'),
            'organization_id' => $this->organization->getKey(),
            'organization_name' => $this->organization->name,
            'old_plan' => $this->oldPlan->value,
            'new_plan' => $this->newPlan->value,
            'reason' => $this->reason,
        ];
    }
}
