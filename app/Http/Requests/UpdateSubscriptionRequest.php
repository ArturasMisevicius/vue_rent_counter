<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
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
            'plan_type' => ['required', Rule::in(SubscriptionPlanType::values())],
            'status' => ['required', Rule::in(SubscriptionStatus::values())],
            'starts_at' => ['sometimes', 'date'],
            'expires_at' => ['required', 'date'],
            'max_properties' => ['required', 'integer', 'min:1'],
            'max_tenants' => ['required', 'integer', 'min:1'],
            'auto_renew' => ['sometimes', 'boolean'],
            'renewal_period' => ['sometimes', Rule::in(['monthly', 'quarterly', 'annually'])],
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
            'plan_type.required' => __('subscriptions.validation.plan_type.required'),
            'plan_type.in' => __('subscriptions.validation.plan_type.in'),
            'status.required' => __('subscriptions.validation.status.required'),
            'status.in' => __('subscriptions.validation.status.in'),
            'expires_at.required' => __('subscriptions.validation.expires_at.required'),
            'expires_at.date' => __('subscriptions.validation.expires_at.date'),
            'max_properties.required' => __('subscriptions.validation.max_properties.required'),
            'max_properties.integer' => __('subscriptions.validation.max_properties.integer'),
            'max_properties.min' => __('subscriptions.validation.max_properties.min'),
            'max_tenants.required' => __('subscriptions.validation.max_tenants.required'),
            'max_tenants.integer' => __('subscriptions.validation.max_tenants.integer'),
            'max_tenants.min' => __('subscriptions.validation.max_tenants.min'),
        ];
    }
}
