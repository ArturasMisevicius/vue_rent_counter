<?php

namespace App\Http\Requests\Superadmin\Organizations;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Organization|null $organization */
        $organization = $this->route('record');

        return self::ruleset($organization);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function ruleset(?Organization $organization = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')->ignore($organization?->getKey()),
            ],
            'status' => ['required', Rule::enum(OrganizationStatus::class)],
        ];
    }
}
