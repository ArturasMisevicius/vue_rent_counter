<?php

namespace App\Enums\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

/**
 * @mixin \BackedEnum
 */
trait HasTranslatedLabel
{
    /**
     * @return array<int|string, string>
     */
    public static function options(): array
    {
        return collect(static::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    /**
     * @return array<int, int|string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case): int|string => $case->value,
            static::cases(),
        );
    }

    /**
     * @return array<int, int|string>
     */
    public static function onlyValues(self ...$cases): array
    {
        return array_map(
            static fn (self $case): int|string => $case->value,
            $cases,
        );
    }

    /**
     * @return array<int, int|string>
     */
    public static function exceptValues(self ...$cases): array
    {
        return collect(static::cases())
            ->reject(fn (self $candidate): bool => in_array($candidate, $cases, true))
            ->map(static fn (self $case): int|string => $case->value)
            ->values()
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    public static function labels(): array
    {
        return collect(static::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->label();
    }

    public function label(): string
    {
        return (string) __($this->translationKey());
    }

    public static function translationKeyPrefix(): string
    {
        return 'enums.'.Str::of(class_basename(static::class))->snake()->value();
    }

    public function translationKey(): string
    {
        return static::translationKeyPrefix().'.'.$this->value;
    }
}
