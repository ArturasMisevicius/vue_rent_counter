<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Contracts\Validation\Rule;

final class WithinTenantLimit implements Rule
{
    public function __construct(
        private readonly SubscriptionChecker $subscriptionChecker,
    ) {}

    public function passes($attribute, $value): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $this->subscriptionChecker->canCreateTenant($user);
    }

    public function message(): string
    {
        return __('subscriptions.tenant_limit_reached');
    }
}
