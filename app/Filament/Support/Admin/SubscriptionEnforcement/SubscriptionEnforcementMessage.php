<?php

namespace App\Filament\Support\Admin\SubscriptionEnforcement;

use App\Enums\SubscriptionAccessMode;

class SubscriptionEnforcementMessage
{
    /**
     * @return array{title: string, body: string, action_label: string, action_url: string}
     */
    public function forResource(string $resource, SubscriptionAccessState $state): array
    {
        $key = match (true) {
            $state->mode === SubscriptionAccessMode::LIMIT_BLOCKED => "behavior.subscription.limit_blocked.{$resource}",
            $state->mode === SubscriptionAccessMode::GRACE_READ_ONLY => 'behavior.subscription.grace_read_only',
            $state->mode === SubscriptionAccessMode::SUSPENDED => 'behavior.subscription.suspended',
            default => 'behavior.subscription.post_grace_read_only',
        };

        return [
            'title' => __($key.'.title'),
            'body' => __($key.'.body', [
                'limit' => $state->limits[$resource] ?? null,
                'used' => $state->usage[$resource] ?? null,
                'grace_ends_at' => $state->graceEndsAt?->toDateString(),
            ]),
            'action_label' => __('behavior.subscription.actions.manage'),
            'action_url' => route('filament.admin.pages.settings').'#subscription',
        ];
    }
}
