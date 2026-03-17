<?php

namespace App\Filament\Support\Admin\SubscriptionEnforcement;

use App\Enums\SubscriptionAccessMode;
use App\Models\Subscription;
use Carbon\CarbonInterface;

class SubscriptionAccessState
{
    /**
     * @param  list<string>  $limitHits
     * @param  array<string, int>  $usage
     * @param  array<string, int|null>  $limits
     */
    public function __construct(
        public readonly SubscriptionAccessMode $mode,
        public readonly ?Subscription $subscription = null,
        public readonly array $limitHits = [],
        public readonly ?CarbonInterface $graceEndsAt = null,
        public readonly array $usage = [],
        public readonly array $limits = [],
    ) {}

    public function canWrite(): bool
    {
        return ! $this->isReadOnly();
    }

    public function isReadOnly(): bool
    {
        return in_array($this->mode, [
            SubscriptionAccessMode::GRACE_READ_ONLY,
            SubscriptionAccessMode::POST_GRACE_READ_ONLY,
        ], true);
    }

    public function hidesWriteActions(): bool
    {
        return $this->mode === SubscriptionAccessMode::POST_GRACE_READ_ONLY;
    }

    public function blocksCreation(string $resource): bool
    {
        if ($this->isReadOnly()) {
            return true;
        }

        return $this->isLimitBlocked($resource);
    }

    public function isLimitBlocked(string $resource): bool
    {
        return in_array($resource, $this->limitHits, true);
    }
}
