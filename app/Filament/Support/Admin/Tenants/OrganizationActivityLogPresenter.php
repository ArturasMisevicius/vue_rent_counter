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
        $action = (string) $record->action;

        return match ($mutation) {
            'invoice.finalized' => __('superadmin.audit_logs.actions.finalized'),
            'invoice.payment_recorded' => __('superadmin.audit_logs.actions.payment_processed'),
            'meter_reading.validate' => __('superadmin.audit_logs.actions.meter_reading_validated'),
            'meter_reading.reject' => __('superadmin.audit_logs.actions.meter_reading_rejected'),
            default => self::translatedActionLabel($action),
        };
    }

    public static function resourceLabel(OrganizationActivityLog $record): string
    {
        $resource = self::resourceTypeLabel($record->resource_type)
            ?? __('superadmin.organizations.relations.activity_logs.placeholders.organization');

        if ($record->resource_id === null) {
            return $resource;
        }

        return "{$resource} #{$record->resource_id}";
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
                    'label' => self::fieldLabel($key),
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
            is_bool($value) => $value ? __('superadmin.audit_logs.placeholders.yes') : __('superadmin.audit_logs.placeholders.no'),
            is_array($value) => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            default => (string) $value,
        };
    }

    private static function fieldLabel(string $key): string
    {
        $segment = Str::afterLast($key, '.');

        $genericTranslationKey = "superadmin.audit_logs.diff.fields.{$segment}";

        if (__($genericTranslationKey) !== $genericTranslationKey) {
            return __($genericTranslationKey);
        }

        $translationKey = match ($segment) {
            'name' => 'superadmin.organizations.relations.users.columns.name',
            'email' => 'superadmin.organizations.relations.users.columns.email',
            'status' => 'superadmin.organizations.relations.users.columns.status',
            'locale' => 'superadmin.users.fields.locale',
            'role' => 'superadmin.organizations.relations.users.columns.role',
            'plan' => 'superadmin.subscriptions_resource.fields.plan',
            'starts_at' => 'superadmin.subscriptions_resource.fields.starts_at',
            'expires_at' => 'superadmin.subscriptions_resource.fields.expires_at',
            'organization_id' => 'superadmin.organizations.singular',
            'user_id' => 'superadmin.users.singular',
            default => null,
        };

        if ($translationKey !== null) {
            return __($translationKey);
        }

        return Str::of($segment)->headline()->toString();
    }

    private static function resourceTypeLabel(?string $resourceType): ?string
    {
        if (blank($resourceType)) {
            return null;
        }

        $segment = Str::snake(class_basename($resourceType));
        $translationKey = "superadmin.audit_logs.record_types.{$segment}";

        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }

        return Str::of(class_basename($resourceType))->headline()->toString();
    }

    private static function translatedActionLabel(string $action): string
    {
        $activityTranslationKey = 'superadmin.audit_logs.actions.'.Str::of($action)
            ->replace(['.', '-'], '_')
            ->snake()
            ->toString();

        if (__($activityTranslationKey) !== $activityTranslationKey) {
            return __($activityTranslationKey);
        }

        $translationKey = "enums.audit_log_action.{$action}";

        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }

        return Str::of($action)->replace('_', ' ')->headline()->toString();
    }
}
