<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Kyc;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RejectKycProfileRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdminLike();
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'rejection_reason',
        ]);
    }
}
