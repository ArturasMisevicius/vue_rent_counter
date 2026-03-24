<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceDraftRequest extends FormRequest
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
            'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:billing_period_end'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.period' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.rate' => ['required', 'numeric'],
            'items.*.total' => ['required', 'numeric'],
            'adjustments' => ['sometimes', 'array'],
            'adjustments.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'adjustments.*.amount' => ['sometimes', 'nullable', 'numeric'],
            'notes' => ['sometimes', 'nullable', 'string'],
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
            'due_date.date' => ['date', 'due_date'],
            'due_date.after_or_equal' => ['after_or_equal', 'due_date', [
                'date' => $this->translateAttribute('billing_period_end'),
            ]],
            'items.required' => ['required', 'items'],
            'items.array' => ['array', 'items'],
            'items.min' => ['min.array', 'items', [
                'min' => 1,
            ]],
            'items.*.description.required' => ['required', 'items'],
            'items.*.quantity.required' => ['required', 'items'],
            'items.*.quantity.numeric' => ['numeric', 'items'],
            'items.*.quantity.min' => ['min.numeric', 'items', [
                'min' => 0,
            ]],
            'items.*.rate.required' => ['required', 'items'],
            'items.*.rate.numeric' => ['numeric', 'items'],
            'items.*.total.required' => ['required', 'items'],
            'items.*.total.numeric' => ['numeric', 'items'],
            'adjustments.array' => ['array', 'adjustments'],
            'adjustments.*.amount.numeric' => ['numeric', 'adjustments'],
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
            'due_date',
            'items',
            'adjustments',
            'notes',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'organization_id',
            'tenant_user_id',
            'billing_period_start',
            'billing_period_end',
            'due_date',
            'notes',
        ]);

        $this->emptyStringsToNull([
            'organization_id',
            'tenant_user_id',
            'billing_period_start',
            'billing_period_end',
            'due_date',
            'notes',
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
