<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Filament\Support\Admin\Leads\LeadCsvMappingPreset;

class MapLeadCsvColumns
{
    /**
     * @param  list<string>  $headers
     * @param  array<string, string|null>  $submittedMapping
     * @return array<string, string>
     */
    public function handle(array $headers, array $submittedMapping = []): array
    {
        $mapping = [];
        $headersByNormalizedName = collect($headers)
            ->mapWithKeys(fn (string $header): array => [$this->normalize($header) => $header])
            ->all();

        foreach (LeadCsvMappingPreset::systemFields() as $field) {
            $submittedHeader = $submittedMapping[$field] ?? null;

            if (is_string($submittedHeader) && in_array($submittedHeader, $headers, true)) {
                $mapping[$field] = $submittedHeader;

                continue;
            }

            foreach (LeadCsvMappingPreset::aliases()[$field] ?? [] as $alias) {
                $normalizedAlias = $this->normalize($alias);

                if (array_key_exists($normalizedAlias, $headersByNormalizedName)) {
                    $mapping[$field] = $headersByNormalizedName[$normalizedAlias];

                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalize(string $value): string
    {
        return (string) str($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }
}
