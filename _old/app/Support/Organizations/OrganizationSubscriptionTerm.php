<?php

declare(strict_types=1);

namespace App\Support\Organizations;

use Illuminate\Support\Carbon;

final class OrganizationSubscriptionTerm
{
    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            1 => 'One Month',
            3 => 'Three Months',
            6 => 'Six Months',
            12 => 'Twelve Months',
        ];
    }

    public static function expiresAt(int|string|null $months, ?Carbon $startAt = null): Carbon
    {
        $term = self::normalizeMonths($months);

        return ($startAt ?? now())
            ->copy()
            ->addMonthsNoOverflow($term)
            ->endOfDay();
    }

    public static function previewText(int|string|null $months, ?Carbon $startAt = null): string
    {
        return 'Subscription will expire on '.self::expiresAt($months, $startAt)->translatedFormat('F j, Y');
    }

    private static function normalizeMonths(int|string|null $months): int
    {
        $term = (int) ($months ?: 1);

        if (! array_key_exists($term, self::options())) {
            return 1;
        }

        return $term;
    }
}
