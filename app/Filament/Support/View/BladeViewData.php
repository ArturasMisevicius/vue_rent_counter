<?php

declare(strict_types=1);

namespace App\Filament\Support\View;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class BladeViewData
{
    /**
     * @return array{classes: string, translation_key: string}
     */
    public static function statusBadge(mixed $status, ?Model $model = null): array
    {
        $statusValue = $status instanceof BackedEnum ? (string) $status->value : (string) $status;
        $translationKey = null;

        if ($status instanceof BackedEnum && method_exists($status, 'translationKey')) {
            $translationKey = $status->translationKey();
        }

        if ($translationKey === null && $model instanceof Model) {
            $statusCast = $model->getCasts()['status'] ?? null;

            if (is_string($statusCast) && enum_exists($statusCast) && method_exists($statusCast, 'translationKeyPrefix')) {
                $translationKey = $statusCast::translationKeyPrefix().'.'.$statusValue;
            } else {
                $translationKey = 'enums.'.Str::snake(class_basename($model)).'_status.'.$statusValue;
            }
        }

        return [
            'classes' => self::statusBadgeClasses($statusValue),
            'translation_key' => $translationKey ?? 'enums.status.'.$statusValue,
        ];
    }

    public static function statusBadgeClasses(string $status): string
    {
        return [
            'draft' => 'bg-slate-100 text-slate-700 ring-slate-300/80',
            'scheduled' => 'bg-sky-50 text-sky-700 ring-sky-300/80',
            'pending' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
            'finalized' => 'bg-brand-ink/10 text-brand-ink ring-brand-ink/15',
            'partially_paid' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
            'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
            'active' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
            'valid' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
            'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
            'overdue' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
            'failed' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
            'rejected' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
            'suspended' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
            'warning' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
            'flagged' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
            'degraded' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
            'void' => 'bg-slate-200 text-slate-700 ring-slate-300/80',
            'inactive' => 'bg-slate-100 text-slate-700 ring-slate-300/80',
            'info' => 'bg-sky-50 text-sky-800 ring-sky-300/80',
            'sent' => 'bg-sky-50 text-sky-800 ring-sky-300/80',
            'healthy' => 'bg-emerald-50 text-emerald-800 ring-emerald-300/80',
            'low' => 'bg-slate-100 text-slate-700 ring-slate-300/80',
            'medium' => 'bg-amber-50 text-amber-800 ring-amber-300/80',
            'high' => 'bg-rose-50 text-rose-800 ring-rose-300/80',
            'critical' => 'bg-rose-100 text-rose-900 ring-rose-400/80',
        ][$status] ?? 'bg-slate-100 text-slate-700 ring-slate-300/80';
    }

    public static function alertClasses(string $type): string
    {
        return [
            'info' => 'border-sky-200 bg-sky-50 text-sky-900',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
            'danger' => 'border-rose-200 bg-rose-50 text-rose-900',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        ][$type] ?? 'border-sky-200 bg-sky-50 text-sky-900';
    }

    /**
     * @return array<int|string, string|bool>
     */
    public static function tenantCardClasses(string $tone, bool $hasHref): array
    {
        return [
            'rounded-3xl border px-5 py-5',
            'border-slate-200 bg-slate-50' => $tone === 'muted',
            'border-slate-200 bg-white' => $tone === 'white',
            'border-emerald-200/70 bg-white shadow-sm' => $tone === 'success',
            'border-amber-200 bg-amber-50/70' => $tone === 'warning',
            'transition hover:border-slate-300 hover:bg-white focus:outline-none focus:ring-2 focus:ring-brand-mint/35' => $hasHref,
        ];
    }

    /**
     * @return array<int|string, string|bool>
     */
    public static function tenantActionClasses(string $variant): array
    {
        return [
            'inline-flex min-h-11 touch-manipulation items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-brand-mint/35 disabled:cursor-wait disabled:opacity-70',
            'bg-brand-ink text-white hover:bg-slate-900' => $variant === 'primary',
            'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' => $variant === 'secondary',
            'bg-slate-50 text-slate-700 hover:bg-white' => $variant === 'soft',
            'border border-amber-200 bg-white text-slate-800 shadow-sm hover:bg-amber-100 focus:ring-amber-400/40' => $variant === 'warning',
        ];
    }

    public static function organizationUsageToneClass(string $tone): string
    {
        return [
            'default' => 'bg-slate-900',
            'warning' => 'bg-amber-500',
            'danger' => 'bg-red-600',
            'info' => 'bg-sky-500',
            'success' => 'bg-emerald-500',
        ][$tone] ?? 'bg-slate-900';
    }

    public static function projectVarianceToneClass(string $tone): string
    {
        return [
            'danger' => 'bg-danger-50 text-danger-700 ring-danger-200 dark:bg-danger-500/10 dark:text-danger-300 dark:ring-danger-500/30',
            'success' => 'bg-success-50 text-success-700 ring-success-200 dark:bg-success-500/10 dark:text-success-300 dark:ring-success-500/30',
            'info' => 'bg-info-50 text-info-700 ring-info-200 dark:bg-info-500/10 dark:text-info-300 dark:ring-info-500/30',
            'gray' => 'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
            'warning' => 'bg-warning-50 text-warning-700 ring-warning-200 dark:bg-warning-500/10 dark:text-warning-300 dark:ring-warning-500/30',
        ][$tone] ?? 'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10';
    }

    public static function settingsUsageBarClass(string $tone): string
    {
        return match ($tone) {
            'danger' => 'bg-rose-500',
            'warning' => 'bg-amber-500',
            default => 'bg-slate-900',
        };
    }

    public static function adminTopCardToneClass(string $tone): string
    {
        return match ($tone) {
            'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'info' => 'border-sky-200 bg-sky-50 text-sky-700',
            default => 'border-slate-200 bg-white text-slate-700',
        };
    }

    public static function adminPriorityClass(string $priority): string
    {
        return match ($priority) {
            'high' => 'bg-rose-100 text-rose-700',
            'medium' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public static function adminStageClass(string $tone): string
    {
        return match ($tone) {
            'warning' => 'border-amber-200 bg-amber-50',
            'success' => 'border-emerald-200 bg-emerald-50',
            'info' => 'border-sky-200 bg-sky-50',
            default => 'border-slate-200 bg-white',
        };
    }

    public static function adminSectionCardClass(string $tone): string
    {
        return match ($tone) {
            'danger' => 'border-rose-200 hover:border-rose-300',
            'warning' => 'border-amber-200 hover:border-amber-300',
            'success' => 'border-emerald-200 hover:border-emerald-300',
            'info' => 'border-sky-200 hover:border-sky-300',
            default => 'border-slate-200 hover:border-slate-300',
        };
    }

    public static function progressPercentage(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    public static function dossierFieldLabel(string $key): string
    {
        $translationKey = 'superadmin.users.dossier.fields.'.LocalizedCodeLabel::segment($key);

        return trans()->has($translationKey) ? __($translationKey) : Str::headline($key);
    }

    public static function translatedOrFallback(string $key, string $fallback): string
    {
        $label = __($key);

        return $label === $key ? $fallback : $label;
    }

    public static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * @param  Collection<int, mixed>  $readings
     * @return list<array{x: float, y: float}>
     */
    public static function meterChartPoints(
        Collection $readings,
        int $width,
        int $height,
        int $paddingX,
        int $paddingY,
        mixed $minValue,
        mixed $maxValue,
    ): array {
        $count = max($readings->count() - 1, 1);
        $chartWidth = $width - ($paddingX * 2);
        $chartHeight = $height - ($paddingY * 2);
        $range = max(((float) $maxValue) - ((float) $minValue), 1.0);

        return $readings
            ->values()
            ->map(fn (mixed $reading, int $index): array => [
                'x' => round($paddingX + (($chartWidth / $count) * $index), 2),
                'y' => round($paddingY + ($chartHeight - ((((float) $reading->reading_value) - (float) $maxValue + $range) / $range * $chartHeight)), 2),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $chart
     * @return array<string, mixed>
     */
    public static function revenueTrendChart(array $chart): array
    {
        $sourceLabels = array_values($chart['labels'] ?? []);
        $sourceSeries = array_values($chart['series'] ?? []);
        $allPoints = collect($sourceSeries)->flatMap(fn (array $line): array => $line['points'] ?? []);
        $hasData = $allPoints->contains(fn (float|int $value): bool => $value > 0);
        $maxValue = max(1, (float) ($allPoints->max() ?? 0));
        $tickCount = 4;
        $width = 760;
        $height = 300;
        $paddingLeft = 56;
        $paddingRight = 18;
        $paddingTop = 20;
        $paddingBottom = 42;
        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $labelCount = max(1, count($sourceLabels) - 1);

        $ticks = collect(range(0, $tickCount))
            ->map(fn (int $index): array => [
                'value' => EuMoneyFormatter::format(round($maxValue - (($maxValue / $tickCount) * $index), 2)),
                'y' => round($paddingTop + (($plotHeight / $tickCount) * $index), 2),
            ])
            ->all();

        $labels = collect($sourceLabels)
            ->map(fn (string $label, int $index): array => [
                'label' => $label,
                'x' => round($paddingLeft + (($plotWidth / $labelCount) * $index), 2),
            ])
            ->all();

        $series = collect($sourceSeries)
            ->map(function (array $line) use ($labelCount, $maxValue, $paddingLeft, $paddingTop, $plotHeight, $plotWidth, $sourceLabels): array {
                $points = collect($line['points'] ?? [])
                    ->map(function (float|int $value, int $index) use ($labelCount, $line, $maxValue, $paddingLeft, $paddingTop, $plotHeight, $plotWidth, $sourceLabels): array {
                        $x = $paddingLeft + (($plotWidth / $labelCount) * $index);
                        $normalizedValue = $maxValue <= 0 ? 0 : ((float) $value / $maxValue);
                        $y = $paddingTop + $plotHeight - ($plotHeight * $normalizedValue);

                        return [
                            'x' => round($x, 2),
                            'y' => round($y, 2),
                            'month' => $sourceLabels[$index] ?? '',
                            'formatted' => $line['formatted'][$index] ?? EuMoneyFormatter::format(0),
                        ];
                    })
                    ->all();

                $polyline = collect($points)
                    ->map(fn (array $point): string => "{$point['x']},{$point['y']}")
                    ->implode(' ');

                return [
                    ...$line,
                    'points' => $points,
                    'polyline' => $polyline,
                ];
            })
            ->all();

        return [
            'has_data' => $hasData,
            'width' => $width,
            'height' => $height,
            'padding_left' => $paddingLeft,
            'padding_right' => $paddingRight,
            'ticks' => $ticks,
            'labels' => $labels,
            'series' => $series,
        ];
    }
}
