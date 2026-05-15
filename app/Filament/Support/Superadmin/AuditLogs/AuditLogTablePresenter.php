<?php

namespace App\Filament\Support\Superadmin\AuditLogs;

use App\Enums\AuditLogAction;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
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
            self::FINALIZED_ACTION => __('superadmin.audit_logs.actions.finalized'),
            self::PAYMENT_PROCESSED_ACTION => __('superadmin.audit_logs.actions.payment_processed'),
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
        $translationKey = "enums.audit_log_action.{$actionType}";

        if (__($translationKey) !== $translationKey) {
            return __($translationKey);
        }

        return self::actionTypeOptions()[$actionType]
            ?? Str::of($actionType)->replace('_', ' ')->headline()->toString();
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

    public static function actionFilterValue(AuditLog $record): string
    {
        return self::actionType($record);
    }

    public static function actorLabel(AuditLog $record): string
    {
        $impersonatedName = data_get($record->metadata, 'impersonation.impersonated_user.name');

        if (filled($impersonatedName)) {
            return __('superadmin.audit_logs.placeholders.impersonated', ['name' => $impersonatedName]);
        }

        return $record->actor?->name ?? __('superadmin.audit_logs.placeholders.system');
    }

    public static function actorDescription(AuditLog $record): ?string
    {
        $impersonatorEmail = data_get($record->metadata, 'impersonation.impersonator.email');

        if (filled($impersonatorEmail)) {
            return $impersonatorEmail;
        }

        return $record->actor?->email;
    }

    public static function feedLabel(AuditLog $record): string
    {
        $description = trim((string) $record->description);
        $defaultDescription = trim(sprintf(
            '%s %s',
            class_basename((string) $record->subject_type),
            $record->action?->value ?? (string) $record->getAttribute('action'),
        ));

        if ($description !== '' && $description !== $defaultDescription) {
            return app(DatabaseContentLocalizer::class)->activityDescription($description) ?? $description;
        }

        return self::actionLabel($record);
    }

    public static function recordTypeLabel(?string $subjectType): string
    {
        if (blank($subjectType)) {
            return __('superadmin.audit_logs.placeholders.unknown');
        }

        return AuditLog::subjectTypeLabel($subjectType);
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
                $beforeValue = $before[$key] ?? __('superadmin.audit_logs.placeholders.empty');
                $afterValue = $after[$key] ?? __('superadmin.audit_logs.placeholders.empty');

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
            $value === null => __('superadmin.audit_logs.placeholders.empty'),
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
}
