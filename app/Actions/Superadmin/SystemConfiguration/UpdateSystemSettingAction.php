<?php

namespace App\Actions\Superadmin\SystemConfiguration;

use App\Http\Requests\Superadmin\SystemConfiguration\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Validator;

class UpdateSystemSettingAction
{
    public function __invoke(SystemSetting $setting, mixed $value): SystemSetting
    {
        $data = Validator::make([
            'value' => $value,
        ], UpdateSystemSettingRequest::rulesFor($setting))->validate();

        $setting->update([
            'value' => $this->normalizeValue($setting, $data['value']),
        ]);

        return $setting->refresh();
    }

    private function normalizeValue(SystemSetting $setting, mixed $value): string
    {
        return match ($setting->type) {
            'boolean' => $value ? 'true' : 'false',
            'integer' => (string) (int) $value,
            default => (string) $value,
        };
    }
}
