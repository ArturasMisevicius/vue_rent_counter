<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class SendInvoiceEmailRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdminLike() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_email' => ['required', 'email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'recipient_email.required' => ['required', 'recipient_email'],
            'recipient_email.email' => ['email', 'recipient_email'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'recipient_email',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'recipient_email',
        ]);
    }
}
