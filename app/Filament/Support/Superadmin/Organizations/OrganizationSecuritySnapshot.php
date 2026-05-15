<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Enums\SecurityViolationSeverity;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class OrganizationSecuritySnapshot
{
    /**
     * @param  list<array{key: string, label: string, count: int, tone: string}>  $severityCards
     * @param  list<array{name: string, last_login_at: string}>  $userLastLogins
     */
    public function __construct(
        public array $severityCards,
        public int $unreviewedCount,
        public array $userLastLogins,
        public string $securityViolationsUrl,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        $recentViolations = $organization->securityViolations()
            ->select([
                'id',
                'organization_id',
                'user_id',
                'severity',
                'metadata',
                'occurred_at',
            ])
            ->occurredSince(now()->subDays(30)->startOfDay())
            ->get();

        $userLastLogins = $organization->users()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'last_login_at',
            ])
            ->orderedByName()
            ->get()
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'last_login_at' => $user->last_login_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat())
                    ?? __('superadmin.organizations.overview.placeholders.not_available'),
            ])
            ->all();

        return new self(
            severityCards: self::severityCards($recentViolations),
            unreviewedCount: $recentViolations
                ->filter(fn (SecurityViolation $violation): bool => ! $violation->isReviewed())
                ->count(),
            userLastLogins: $userLastLogins,
            securityViolationsUrl: route('filament.admin.resources.security-violations.index').'?'.http_build_query([
                'tableFilters' => [
                    'organization' => [
                        'value' => $organization->getKey(),
                    ],
                ],
            ]),
        );
    }

    /**
     * @param  Collection<int, SecurityViolation>  $recentViolations
     * @return list<array{key: string, label: string, count: int, tone: string}>
     */
    private static function severityCards($recentViolations): array
    {
        return [
            self::severityCard('critical', SecurityViolationSeverity::CRITICAL, 'danger', $recentViolations),
            self::severityCard('high', SecurityViolationSeverity::HIGH, 'warning', $recentViolations),
            self::severityCard('medium', SecurityViolationSeverity::MEDIUM, 'info', $recentViolations),
            self::severityCard('low', SecurityViolationSeverity::LOW, 'default', $recentViolations),
        ];
    }

    /**
     * @param  Collection<int, SecurityViolation>  $recentViolations
     * @return array{key: string, label: string, count: int, tone: string}
     */
    private static function severityCard(
        string $key,
        SecurityViolationSeverity $severity,
        string $tone,
        $recentViolations,
    ): array {
        return [
            'key' => $key,
            'label' => __("superadmin.organizations.overview.security_health_labels.{$key}"),
            'count' => $recentViolations
                ->filter(fn (SecurityViolation $violation): bool => $violation->severity === $severity)
                ->count(),
            'tone' => $tone,
        ];
    }
}
