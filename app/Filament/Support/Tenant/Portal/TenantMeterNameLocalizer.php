<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenant\Portal;

use App\Models\Meter;
use Illuminate\Support\Str;

class TenantMeterNameLocalizer
{
    public function displayName(?Meter $meter): string
    {
        if (! $meter instanceof Meter) {
            return __('dashboard.not_available');
        }

        $name = trim((string) $meter->name);

        if ($name === '') {
            return __('dashboard.not_available');
        }

        if (preg_match('/^Meter (?<number>[A-Za-z0-9-]+)$/', $name, $matches) === 1) {
            return __('tenant.pages.property.generic_meter_label', [
                'number' => $matches['number'],
            ]);
        }

        if ($this->isGeneratedDemoMeterName($meter, $name)) {
            return __('tenant.pages.property.demo_meter_label', [
                'type' => $meter->type?->label() ?? __('tenant.pages.property.meter_label'),
            ]);
        }

        if ($this->isOperationsDemoMeterName($meter, $name)) {
            return __('tenant.pages.property.operations_demo_meter_label', [
                'type' => $meter->type?->label() ?? __('tenant.pages.property.meter_label'),
            ]);
        }

        return $name;
    }

    private function isGeneratedDemoMeterName(Meter $meter, string $name): bool
    {
        return collect($this->supportedLocales())
            ->contains(function (string $locale) use ($meter, $name): bool {
                $type = $this->localizedMeterType($meter, $locale);

                return $this->sameName(
                    $name,
                    __('tenant.pages.property.demo_meter_label', ['type' => $type], $locale),
                ) || $this->sameName($name, sprintf('Demo %s Meter', $type));
            });
    }

    private function isOperationsDemoMeterName(Meter $meter, string $name): bool
    {
        if ($this->sameName($name, 'Operations Demo Meter')) {
            return true;
        }

        return collect($this->supportedLocales())
            ->contains(fn (string $locale): bool => $this->sameName(
                $name,
                __('tenant.pages.property.operations_demo_meter_label', [
                    'type' => $this->localizedMeterType($meter, $locale),
                ], $locale),
            ));
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        $supportedLocales = config('app.supported_locales', ['en' => 'English']);
        $locales = is_array($supportedLocales) ? array_keys($supportedLocales) : [];

        return array_values(array_unique(array_merge(['en'], $locales)));
    }

    private function localizedMeterType(Meter $meter, string $locale): string
    {
        if ($meter->type === null) {
            return __('tenant.pages.property.meter_label', [], $locale);
        }

        return (string) __($meter->type->translationKey(), [], $locale);
    }

    private function sameName(string $left, string $right): bool
    {
        return Str::of($left)->squish()->lower()->exactly(
            Str::of($right)->squish()->lower()->value(),
        );
    }
}
