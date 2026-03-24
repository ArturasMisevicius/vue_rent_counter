<?php

declare(strict_types=1);

namespace App\Filament\Support\Superadmin\SystemConfiguration;

use App\Enums\SystemSettingCategory;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;

final class SystemSettingCatalog
{
    /**
     * @return array<string, array{category: SystemSettingCategory, label: string}>
     */
    public function categories(): array
    {
        return [
            SystemSettingCategory::BILLING->value => [
                'category' => SystemSettingCategory::BILLING,
                'label' => 'Billing',
            ],
            SystemSettingCategory::NOTIFICATIONS->value => [
                'category' => SystemSettingCategory::NOTIFICATIONS,
                'label' => 'Notifications',
            ],
            SystemSettingCategory::SECURITY->value => [
                'category' => SystemSettingCategory::SECURITY,
                'label' => 'Security',
            ],
            SystemSettingCategory::SUBSCRIPTION->value => [
                'category' => SystemSettingCategory::SUBSCRIPTION,
                'label' => 'Subscription Limits',
            ],
            SystemSettingCategory::EMAIL->value => [
                'category' => SystemSettingCategory::EMAIL,
                'label' => 'Email',
            ],
            SystemSettingCategory::LOCALIZATION->value => [
                'category' => SystemSettingCategory::LOCALIZATION,
                'label' => 'Localization',
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     category: SystemSettingCategory,
     *     label: string,
     *     description: string,
     *     editor: 'text'|'email'|'boolean'|'number'|'list',
     *     default: string|int|bool|list<string>,
     *     rules: list<string>
     * }>
     */
    public function definitions(): array
    {
        return [
            'platform.billing.currency' => [
                'category' => SystemSettingCategory::BILLING,
                'label' => 'Billing Currency',
                'description' => 'Default billing currency for platform invoices.',
                'editor' => 'text',
                'default' => 'EUR',
                'rules' => ['required', 'string', 'max:10'],
            ],
            'platform.notifications.email.enabled' => [
                'category' => SystemSettingCategory::NOTIFICATIONS,
                'label' => 'Email Notifications Enabled',
                'description' => 'Enables email delivery for platform notifications.',
                'editor' => 'boolean',
                'default' => true,
                'rules' => ['required', 'string', 'in:true,false,1,0,yes,no,on,off'],
            ],
            'platform.security.require_mfa' => [
                'category' => SystemSettingCategory::SECURITY,
                'label' => 'Require MFA',
                'description' => 'Requires multi-factor authentication for superadmin accounts.',
                'editor' => 'boolean',
                'default' => false,
                'rules' => ['required', 'string', 'in:true,false,1,0,yes,no,on,off'],
            ],
            'platform.subscription.default_property_limit' => [
                'category' => SystemSettingCategory::SUBSCRIPTION,
                'label' => 'Default Property Limit',
                'description' => 'Sets the default property cap applied to subscription limits.',
                'editor' => 'number',
                'default' => 25,
                'rules' => ['required', 'integer', 'min:1'],
            ],
            'platform.email.from_address' => [
                'category' => SystemSettingCategory::EMAIL,
                'label' => 'Default From Address',
                'description' => 'Defines the sender address used for platform emails.',
                'editor' => 'email',
                'default' => (string) config('mail.from.address', 'platform@example.test'),
                'rules' => ['required', 'email'],
            ],
            'platform.localization.supported_locales' => [
                'category' => SystemSettingCategory::LOCALIZATION,
                'label' => 'Supported Locales',
                'description' => 'Lists the locales available across the platform.',
                'editor' => 'list',
                'default' => array_values(array_keys(config('app.supported_locales', ['en' => 'English', 'lt' => 'Lietuvių']))),
                'rules' => ['required', 'string'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return array{
     *     category: SystemSettingCategory,
     *     label: string,
     *     description: string,
     *     editor: 'text'|'email'|'boolean'|'number'|'list',
     *     default: string|int|bool|list<string>,
     *     rules: list<string>
     * }|null
     */
    public function definitionFor(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public function validationRulesFor(string $key): array
    {
        return $this->definitionFor($key)['rules'] ?? ['required', 'string'];
    }

    public function draftValue(SystemSetting $setting): string
    {
        return $this->displayValue(data_get($setting->value, 'value'));
    }

    public function displayValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): string => trim((string) $item))
                ->filter()
                ->implode(', ');
        }

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    public function normalizeValue(string $key, mixed $value): mixed
    {
        $normalized = trim((string) $value);
        $definition = $this->definitionFor($key);

        return match ($definition['editor'] ?? 'text') {
            'boolean' => $this->normalizeBoolean($normalized),
            'number' => (int) $normalized,
            'list' => collect(explode(',', $normalized))
                ->map(fn (string $locale): string => trim($locale))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            default => $normalized,
        };
    }

    /**
     * @param  Collection<int, SystemSetting>  $settings
     * @return Collection<int, array{
     *     key: string,
     *     label: string,
     *     rows: Collection<int, array{
     *         id: int,
     *         key: string,
     *         label: string,
     *         description: string,
     *         editor: 'text'|'email'|'boolean'|'number'|'list',
     *         display_value: string
     *     }>
     * }>
     */
    public function groupedRows(Collection $settings): Collection
    {
        /** @var Collection<string, SystemSetting> $settingsByKey */
        $settingsByKey = $settings->keyBy('key');

        return collect($this->categories())
            ->map(function (array $categoryDefinition, string $categoryKey) use ($settingsByKey): array {
                $rows = collect($this->definitions())
                    ->filter(fn (array $definition): bool => $definition['category']->value === $categoryKey)
                    ->map(function (array $definition, string $key) use ($settingsByKey): ?array {
                        $setting = $settingsByKey->get($key);

                        if (! $setting instanceof SystemSetting) {
                            return null;
                        }

                        return [
                            'id' => $setting->getKey(),
                            'key' => $setting->key,
                            'label' => $definition['label'],
                            'description' => $definition['description'],
                            'editor' => $definition['editor'],
                            'display_value' => $this->displayValue(data_get($setting->value, 'value')),
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'key' => $categoryKey,
                    'label' => $categoryDefinition['label'],
                    'rows' => $rows,
                ];
            })
            ->values();
    }

    /**
     * @return list<array{
     *     key: string,
     *     category: SystemSettingCategory,
     *     label: string,
     *     value: array{value: string|int|bool|list<string>},
     *     is_encrypted: bool
     * }>
     */
    public function seedData(): array
    {
        return collect($this->definitions())
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'category' => $definition['category'],
                'label' => $definition['label'],
                'value' => ['value' => $definition['default']],
                'is_encrypted' => false,
            ])
            ->values()
            ->all();
    }

    private function normalizeBoolean(string $value): bool
    {
        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }
}
