<?php

namespace App\Filament\Pages;

use App\Actions\Superadmin\SystemConfiguration\UpdateSystemSettingAction;
use App\Enums\SystemSettingCategory;
use App\Models\SystemSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class SystemConfiguration extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'System Configuration';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected string $view = 'filament.pages.system-configuration';

    /**
     * @var array<int, mixed>
     */
    public array $settingValues = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function updateSetting(int $settingId): void
    {
        $setting = $this->settingsQuery()
            ->findOrFail($settingId);

        app(UpdateSystemSettingAction::class)(
            $setting,
            $this->settingValues[$settingId] ?? null,
        );

        $this->settingValues[$settingId] = $this->castSettingValueForForm($setting->refresh());

        Notification::make()
            ->title('Setting updated')
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, array{label: string, settings: Collection<int, SystemSetting>}>
     */
    public function getGroupedSettingsProperty(): Collection
    {
        return $this->settingsQuery()
            ->get()
            ->groupBy(fn (SystemSetting $setting): string => $setting->category->value)
            ->map(function (Collection $settings, string $category): array {
                $categoryEnum = SystemSettingCategory::from($category);

                return [
                    'label' => $categoryEnum->label(),
                    'settings' => $settings->values(),
                ];
            })
            ->values();
    }

    private function loadSettings(): void
    {
        $this->settingValues = $this->settingsQuery()
            ->get()
            ->mapWithKeys(fn (SystemSetting $setting): array => [$setting->id => $this->castSettingValueForForm($setting)])
            ->all();
    }

    private function settingsQuery()
    {
        return SystemSetting::query()
            ->select([
                'id',
                'key',
                'category',
                'label',
                'description',
                'type',
                'value',
            ])
            ->orderBy('category')
            ->orderBy('label');
    }

    private function castSettingValueForForm(SystemSetting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => $setting->value === 'true',
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }
}
