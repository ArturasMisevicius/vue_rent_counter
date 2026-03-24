<?php

namespace App\Filament\Actions\Superadmin\SystemConfiguration;

use App\Filament\Support\Superadmin\SystemConfiguration\SystemSettingCatalog;
use App\Http\Requests\Superadmin\SystemConfiguration\UpdateSystemSettingRequest;
use App\Models\SystemSetting;

class UpdateSystemSettingAction
{
    public function __construct(
        private readonly SystemSettingCatalog $catalog,
    ) {}

    public function handle(SystemSetting $setting, array $attributes): SystemSetting
    {
        /** @var UpdateSystemSettingRequest $request */
        $request = new UpdateSystemSettingRequest;
        $validated = $request->validatePayload([
            'key' => $setting->key,
            'value' => $attributes['value'] ?? null,
        ]);

        $setting->update([
            'value' => [
                'value' => $this->catalog->normalizeValue($setting->key, $validated['value']),
            ],
        ]);

        return $setting->fresh();
    }
}
