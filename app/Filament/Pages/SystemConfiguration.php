<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Pages\Page;

class SystemConfiguration extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'system-configuration';

    protected string $view = 'filament.pages.system-configuration';

    public function getTitle(): string
    {
        return 'System Configuration';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getViewData(): array
    {
        return [
            'groups' => SystemSetting::query()
                ->select([
                    'id',
                    'category',
                    'key',
                    'label',
                    'value',
                ])
                ->orderBy('category')
                ->orderBy('label')
                ->get()
                ->groupBy(fn (SystemSetting $setting): string => ucfirst((string) ($setting->category->value ?? $setting->category))),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
