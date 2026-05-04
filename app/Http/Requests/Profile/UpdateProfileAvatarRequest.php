<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Filament\Support\Profile\CroppedAvatarImage;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class UpdateProfileAvatarRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isTenant();
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        $fail(__('validation.image', ['attribute' => $this->translateAttribute($attribute)]));

                        return;
                    }

                    try {
                        CroppedAvatarImage::fromDataUrl($value);
                    } catch (InvalidArgumentException) {
                        $fail(__('validation.image', ['attribute' => $this->translateAttribute($attribute)]));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return $this->translatedMessages([
            'avatar.required' => ['required', 'avatar'],
        ]);
    }

    public function attributes(): array
    {
        return $this->translatedAttributes([
            'avatar',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'avatar',
        ]);
    }
}
