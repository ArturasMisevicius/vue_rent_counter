<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Security;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockIpAddressRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'ip'],
            'reason' => ['required', 'string', 'max:255'],
            'blocked_by_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'blocked_until' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'ip_address.required' => ['required', 'ip_address'],
            'ip_address.ip' => ['ip', 'ip_address'],
            'reason.required' => ['required', 'reason'],
            'reason.max' => ['max.string', 'reason', ['max' => 255]],
            'blocked_by_user_id.required' => ['required', 'user'],
            'blocked_by_user_id.integer' => ['integer', 'user'],
            'blocked_by_user_id.exists' => ['exists', 'user'],
            'blocked_until.date' => ['date', 'blocked_until'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'blocked_by_user_id' => $this->translateAttribute('user'),
            ...$this->translatedAttributes([
                'ip_address',
                'reason',
                'blocked_until',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'ip_address',
            'reason',
            'blocked_by_user_id',
            'blocked_until',
        ]);

        $this->emptyStringsToNull([
            'blocked_until',
        ]);
    }
}
