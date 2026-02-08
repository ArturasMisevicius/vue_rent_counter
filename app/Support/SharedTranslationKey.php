<?php

declare(strict_types=1);

namespace App\Support;

final class SharedTranslationKey
{
    /**
     * @var array<int, string>
     */
    private const ROLE_SEGMENTS = ['superadmin', 'admin', 'manager', 'tenant'];

    public static function normalize(string $key): string
    {
        $segments = explode('.', $key);

        foreach ($segments as $index => $segment) {
            if (in_array($segment, self::ROLE_SEGMENTS, true)) {
                $segments[$index] = 'shared';
            }
        }

        return implode('.', $segments);
    }

    public static function hasRoleSegment(string $key): bool
    {
        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (in_array($segment, self::ROLE_SEGMENTS, true)) {
                return true;
            }
        }

        return false;
    }

    public static function hasSharedSegment(string $key): bool
    {
        return in_array('shared', explode('.', $key), true);
    }

    /**
     * @return array<int, string>
     */
    public static function legacyCandidates(string $sharedKey, ?string $preferredRole = null): array
    {
        $segments = explode('.', $sharedKey);
        $sharedIndexes = [];

        foreach ($segments as $index => $segment) {
            if ($segment === 'shared') {
                $sharedIndexes[] = $index;
            }
        }

        if ($sharedIndexes === []) {
            return [$sharedKey];
        }

        $roleOrder = self::roleOrder($preferredRole);
        $candidates = [];

        self::buildCandidates($segments, $sharedIndexes, $roleOrder, 0, $candidates);

        return array_values(array_unique($candidates));
    }

    /**
     * @return array<int, string>
     */
    private static function roleOrder(?string $preferredRole): array
    {
        $roles = self::ROLE_SEGMENTS;

        if ($preferredRole !== null && in_array($preferredRole, $roles, true)) {
            $roles = array_values(array_filter($roles, fn (string $role): bool => $role !== $preferredRole));
            array_unshift($roles, $preferredRole);
        }

        return $roles;
    }

    /**
     * @param  array<int, string>  $segments
     * @param  array<int, int>  $sharedIndexes
     * @param  array<int, string>  $roleOrder
     * @param  array<int, string>  $candidates
     */
    private static function buildCandidates(
        array $segments,
        array $sharedIndexes,
        array $roleOrder,
        int $level,
        array &$candidates,
    ): void {
        if ($level >= count($sharedIndexes)) {
            $candidates[] = implode('.', $segments);

            return;
        }

        $targetIndex = $sharedIndexes[$level];

        foreach ($roleOrder as $role) {
            $nextSegments = $segments;
            $nextSegments[$targetIndex] = $role;

            self::buildCandidates($nextSegments, $sharedIndexes, $roleOrder, $level + 1, $candidates);
        }
    }
}
