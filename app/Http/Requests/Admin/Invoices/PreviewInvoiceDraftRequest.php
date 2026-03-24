<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewInvoiceDraftRequest extends FormRequest
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
            'organization_id' => [
                Rule::requiredIf(fn (): bool => $this->resolvedOrganizationId() === null),
                'nullable',
                'integer',
                Rule::exists('organizations', 'id'),
            ],
            'tenant_user_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')
                    ->where(fn ($query) => $query
                        ->where('organization_id', $this->resolvedOrganizationId() ?? -1)
                        ->where('role', UserRole::TENANT->value)
                        ->where('status', UserStatus::ACTIVE->value)),
            ],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'organization_id.required' => ['required', 'organization_id'],
            'organization_id.integer' => ['integer', 'organization_id'],
            'organization_id.exists' => ['exists', 'organization_id'],
            'tenant_user_id.required' => ['required', 'tenant_user_id'],
            'tenant_user_id.integer' => ['integer', 'tenant_user_id'],
            'tenant_user_id.exists' => ['exists', 'tenant_user_id'],
            'billing_period_start.required' => ['required', 'billing_period_start'],
            'billing_period_start.date' => ['date', 'billing_period_start'],
            'billing_period_end.required' => ['required', 'billing_period_end'],
            'billing_period_end.date' => ['date', 'billing_period_end'],
            'billing_period_end.after_or_equal' => ['after_or_equal', 'billing_period_end', [
                'date' => $this->translateAttribute('billing_period_start'),
            ]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'organization_id',
            'tenant_user_id',
            'billing_period_start',
            'billing_period_end',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'organization_id',
            'tenant_user_id',
            'billing_period_start',
            'billing_period_end',
        ]);

        $this->emptyStringsToNull([
            'organization_id',
            'tenant_user_id',
            'billing_period_start',
            'billing_period_end',
        ]);
    }

    private function resolvedOrganizationId(): ?int
    {
        $organizationId = $this->input('organization_id');

        if (is_numeric($organizationId)) {
            return (int) $organizationId;
        }

        $user = $this->user();

        if (! $user instanceof User) {
            return null;
        }

        return is_numeric($user->organization_id) ? (int) $user->organization_id : null;
    }
}
