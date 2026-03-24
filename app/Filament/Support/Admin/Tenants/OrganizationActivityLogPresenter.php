<?php

namespace App\Filament\Support\Admin\Tenants;

use App\Models\OrganizationActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrganizationActivityLogPresenter
{
    public static function actionLabel(OrganizationActivityLog $record): string
    {
        $mutation = (string) data_get($record->metadata, 'context.mutation', '');

        return match ($mutation) {
            'invoice.finalized' => 'Finalized',
            'invoice.payment_recorded' => 'Payment Processed',
            default => Str::of((string) $record->action)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    /**
     * @return array<int, array{key: string, label: string, before: string, after: string, changed: bool}>
     */
    public static function diffRows(OrganizationActivityLog $record): array
    {
        $before = self::flattenSnapshot(data_get($record->metadata, 'before', []));
        $after = self::flattenSnapshot(data_get($record->metadata, 'after', []));

        return collect(array_keys($before))
            ->merge(array_keys($after))
            ->unique()
            ->values()
            ->map(function (string $key) use ($before, $after): array {
                $beforeValue = $before[$key] ?? '—';
                $afterValue = $after[$key] ?? '—';

                return [
                    'key' => $key,
                    'label' => Str::of(Str::afterLast($key, '.'))->headline()->toString(),
                    'before' => $beforeValue,
                    'after' => $afterValue,
                    'changed' => $beforeValue !== $afterValue,
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private static function flattenSnapshot(array $values, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value) && Arr::isAssoc($value)) {
                $flattened = [
                    ...$flattened,
                    ...self::flattenSnapshot($value, $path),
                ];

                continue;
            }

            $flattened[$path] = self::formatValue($value);
        }

        return $flattened;
    }

    private static function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'Yes' : 'No',
            is_array($value) => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            default => (string) $value,
        };
    }
}
