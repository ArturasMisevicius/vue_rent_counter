<?php

namespace App\Filament\Pages;

use App\Filament\Actions\Superadmin\SystemConfiguration\UpdateSystemSettingAction;
use App\Filament\Support\Superadmin\SystemConfiguration\SystemSettingCatalog;
use App\Models\SystemSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class SystemConfiguration extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'system-configuration';

    protected string $view = 'filament.pages.system-configuration';

    /**
     * @var array<int, string>
     */
    public array $draftValues = [];

    /**
     * @var array<int, bool>
     */
    public array $editing = [];

    public ?string $savedMessage = null;

    public function getTitle(): string
    {
        return __('superadmin.system_configuration.title');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getViewData(): array
    {
        $catalog = app(SystemSettingCatalog::class);

        return [
            'groups' => $catalog->groupedRows(
                $this->settingsQuery($catalog)->get(),
            ),
        ];
    }

    public function startEditing(int $settingId): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = $this->findEditableSetting($settingId);
        $this->editing[$settingId] = true;
        $this->draftValues[$settingId] = app(SystemSettingCatalog::class)->draftValue($setting);
        $this->savedMessage = null;
    }

    public function cancelEditing(int $settingId): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = $this->findEditableSetting($settingId);
        $this->draftValues[$settingId] = app(SystemSettingCatalog::class)->draftValue($setting);
        $this->editing[$settingId] = false;
        $this->savedMessage = null;
        $this->resetValidation("draftValues.{$settingId}");
    }

    public function saveSetting(int $settingId): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = $this->findEditableSetting($settingId);
        try {
            $updated = app(UpdateSystemSettingAction::class)->handle($setting, [
                'value' => $this->draftValues[$settingId] ?? '',
            ]);
        } catch (ValidationException $exception) {
            $this->resetValidation("draftValues.{$settingId}");
            $this->addError(
                "draftValues.{$settingId}",
                $exception->validator->errors()->first('value'),
            );

            return;
        }

        $this->draftValues[$settingId] = app(SystemSettingCatalog::class)->draftValue($updated);
        $this->editing[$settingId] = false;
        $this->savedMessage = __('superadmin.system_configuration.messages.saved');
        $this->resetValidation("draftValues.{$settingId}");

        Notification::make()
            ->title($this->savedMessage)
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    private function settingsQuery(SystemSettingCatalog $catalog)
    {
        return SystemSetting::query()
            ->select([
                'id',
                'category',
                'key',
                'label',
                'value',
            ])
            ->whereIn('key', $catalog->keys());
    }

    private function findEditableSetting(int $settingId): SystemSetting
    {
        /** @var SystemSetting $setting */
        $setting = $this->settingsQuery(app(SystemSettingCatalog::class))
            ->whereKey($settingId)
            ->firstOrFail();

        return $setting;
    }
}
