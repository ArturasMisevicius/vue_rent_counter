<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait HasTranslatableLabel
{
    /**
     * Get the translation key for the enum case.
     */
    public function translationKey(): string
    {
        $enum = Str::snake(class_basename(static::class));

        return "enums.{$enum}.{$this->value}";
    }

    /**
     * Get a localized label for the enum case.
     */
    public function label(): string
    {
        $translation = __($this->translationKey());

        if ($translation !== $this->translationKey()) {
            return $translation;
        }

        return Str::of($this->value)
            ->replace('_', ' ')
            ->lower()
            ->title();
    }

    /**
     * Support Filament labelable enums.
     */
    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Get all labels keyed by enum value.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return collect(static::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
