<?php

namespace App\Actions\Superadmin\SystemConfiguration;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Validator;

class UpdateSystemSettingAction
{
    public function handle(SystemSetting $setting, array $attributes): SystemSetting
    {
        /** @var array{value: string} $validated */
        $validated = Validator::make($attributes, [
            'value' => ['required', 'string'],
        ])->validate();

        $setting->update([
            'value' => ['value' => $validated['value']],
        ]);

        return $setting->fresh();
    }
}
