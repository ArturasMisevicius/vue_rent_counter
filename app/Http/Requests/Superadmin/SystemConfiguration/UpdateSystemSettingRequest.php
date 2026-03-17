<?php

namespace App\Http\Requests\Superadmin\SystemConfiguration;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rulesFor(SystemSetting $setting): array
    {
        return [
            'value' => match ($setting->type) {
                'boolean' => ['required', 'boolean'],
                'integer' => ['required', 'integer'],
                'email' => ['required', 'email', 'max:255'],
                default => ['required', 'string', 'max:255'],
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var SystemSetting|null $setting */
        $setting = $this->route('setting');

        return $setting instanceof SystemSetting
            ? self::rulesFor($setting)
            : ['value' => ['required']];
    }
}
