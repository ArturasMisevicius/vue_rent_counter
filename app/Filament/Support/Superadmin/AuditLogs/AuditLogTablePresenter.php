<?php

namespace App\Filament\Support\Superadmin\AuditLogs;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogTablePresenter
{
    private const FINALIZED_ACTION = 'finalized';

    private const PAYMENT_PROCESSED_ACTION = 'payment_processed';

    /**
     * @return array<string, string>
     */
    public static function actionTypeOptions(): array
    {
        $priorityOptions = [
            AuditLogAction::CREATED->value => AuditLogAction::CREATED->label(),
            AuditLogAction::UPDATED->value => AuditLogAction::UPDATED->label(),
            AuditLogAction::DELETED->value => AuditLogAction::DELETED->label(),
            self::FINALIZED_ACTION => 'Finalized',
            self::PAYMENT_PROCESSED_ACTION => 'Payment Processed',
        ];

        $remainingOptions = collect(AuditLogAction::cases())
            ->reject(fn (AuditLogAction $case): bool => in_array($case, [
                AuditLogAction::CREATED,
                AuditLogAction::UPDATED,
                AuditLogAction::DELETED,
            ], true))
            ->mapWithKeys(fn (AuditLogAction $case): array => [$case->value => $case->label()])
            ->all();

        return [
            ...$priorityOptions,
            ...$remainingOptions,
        ];
    }

    public static function actionLabel(AuditLog $record): string
    {
        $actionType = self::actionType($record);

        return self::actionTypeOptions()[$actionType]
            ?? Str::of($actionType)->replace('_', ' ')->title()->toString();
    }

    public static function actionColor(AuditLog $record): string
    {
        return match (self::actionType($record)) {
            AuditLogAction::CREATED->value => 'success',
            AuditLogAction::UPDATED->value => 'info',
            AuditLogAction::DELETED->value => 'danger',
            default => 'gray',
        };
    }

    public static function recordTypeLabel(?string $subjectType): string
    {
        if (blank($subjectType)) {
            return 'Unknown';
        }

        return Str::of(class_basename($subjectType))->headline()->toString();
    }

    /**
     * @return array<int, array{key: string, label: string, before: string, after: string, changed: bool}>
     */
    public static function diffRows(AuditLog $record): array
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

    private static function actionType(AuditLog $record): string
    {
        return match (data_get($record->metadata, 'context.mutation')) {
            'invoice.finalized' => self::FINALIZED_ACTION,
            'invoice.payment_recorded' => self::PAYMENT_PROCESSED_ACTION,
            default => $record->action?->value ?? (string) $record->getAttribute('action'),
        };
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
