<?php

namespace App\Filament\Actions\Superadmin\SystemConfiguration;

use App\Http\Requests\Superadmin\SystemConfiguration\UpdateSystemSettingRequest;
use App\Models\SystemSetting;

class UpdateSystemSettingAction
{
    public function handle(SystemSetting $setting, array $attributes): SystemSetting
    {
        /** @var UpdateSystemSettingRequest $request */
        $request = new UpdateSystemSettingRequest;
        $validated = $request->validatePayload($attributes);

        $setting->update([
            'value' => ['value' => $validated['value']],
        ]);

        return $setting->fresh();
    }
}
