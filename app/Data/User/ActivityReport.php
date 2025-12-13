<?php

declare(strict_types=1);

namespace App\Data\User;

use Carbon\Carbon;

final readonly class ActivityReport
{
    public function __construct(
        public int $userId,
        public string $userName,
        public string $userEmail,
        public ?int $tenantId,
        public ?Carbon $lastLoginAt,
        public int $totalSessions,
        public array $recentSessions,
        public int $auditLogEntries,
        public array $recentAuditLogs,
        public array $organizationActivity,
        public Carbon $generatedAt,
    ) {}

    public function isActiveUser(): bool
    {
        return $this->lastLoginAt && $this->lastLoginAt->isAfter(now()->subDays(30));
    }

    public function getActivityScore(): int
    {
        $score = 0;
        
        // Recent login activity
        if ($this->lastLoginAt) {
            $daysSinceLogin = $this->lastLoginAt->diffInDays(now());
            $score += max(0, 30 - $daysSinceLogin);
        }
        
        // Session activity
        $score += min(20, $this->totalSessions);
        
        // Audit log activity
        $score += min(10, $this->auditLogEntries);
        
        // Organization activity
        $score += min(10, count($this->organizationActivity));
        
        return min(100, $score);
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_email' => $this->userEmail,
            'tenant_id' => $this->tenantId,
            'last_login_at' => $this->lastLoginAt?->toISOString(),
            'total_sessions' => $this->totalSessions,
            'recent_sessions' => $this->recentSessions,
            'audit_log_entries' => $this->auditLogEntries,
            'recent_audit_logs' => $this->recentAuditLogs,
            'organization_activity' => $this->organizationActivity,
            'generated_at' => $this->generatedAt->toISOString(),
            'is_active_user' => $this->isActiveUser(),
            'activity_score' => $this->getActivityScore(),
        ];
    }
}