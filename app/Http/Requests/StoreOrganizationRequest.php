<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionPlanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'organization_name' => ['required', 'string', 'max:255'],
            'plan_type' => ['required', Rule::in(SubscriptionPlanType::values())],
            'expires_at' => ['required', 'date', 'after:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('organizations.validation.name.required'),
            'name.string' => __('organizations.validation.name.string'),
            'name.max' => __('organizations.validation.name.max'),
            'email.required' => __('organizations.validation.email.required'),
            'email.string' => __('organizations.validation.email.string'),
            'email.email' => __('organizations.validation.email.email'),
            'email.max' => __('organizations.validation.email.max'),
            'email.unique' => __('organizations.validation.email.unique'),
            'password.required' => __('organizations.validation.password.required'),
            'password.string' => __('organizations.validation.password.string'),
            'password.min' => __('organizations.validation.password.min'),
            'organization_name.required' => __('organizations.validation.organization_name.required'),
            'organization_name.string' => __('organizations.validation.organization_name.string'),
            'organization_name.max' => __('organizations.validation.organization_name.max'),
            'plan_type.required' => __('organizations.validation.plan_type.required'),
            'plan_type.in' => __('organizations.validation.plan_type.in'),
            'expires_at.required' => __('organizations.validation.expires_at.required'),
            'expires_at.date' => __('organizations.validation.expires_at.date'),
            'expires_at.after' => __('organizations.validation.expires_at.after'),
        ];
    }
}
