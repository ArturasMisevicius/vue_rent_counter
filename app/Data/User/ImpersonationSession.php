<?php

declare(strict_types=1);

namespace App\Data\User;

use Carbon\Carbon;

final readonly class ImpersonationSession
{
    public function __construct(
        public string $sessionId,
        public int $adminId,
        public int $targetUserId,
        public Carbon $startedAt,
        public bool $isActive,
    ) {}

    public function getDuration(): int
    {
        return $this->startedAt->diffInMinutes(now());
    }

    public function isExpired(int $maxMinutes = 480): bool // 8 hours default
    {
        return $this->getDuration() > $maxMinutes;
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'admin_id' => $this->adminId,
            'target_user_id' => $this->targetUserId,
            'started_at' => $this->startedAt->toISOString(),
            'is_active' => $this->isActive,
            'duration_minutes' => $this->getDuration(),
        ];
    }
}