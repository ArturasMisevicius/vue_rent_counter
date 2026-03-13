<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'app_name' => [
                'nullable', 
                'string', 
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_\.]+$/', // Allow alphanumeric, spaces, hyphens, underscores, dots
            ],
            'timezone' => [
                'nullable', 
                'string', 
                'in:Europe/Vilnius,Europe/Riga,Europe/Tallinn,UTC,Europe/Warsaw,Europe/Berlin'
            ],
            'language' => [
                'nullable',
                'string',
                'in:en,lt,ru',
            ],
            'date_format' => [
                'nullable',
                'string',
                'in:Y-m-d,d/m/Y,m/d/Y,d.m.Y',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'in:EUR,USD,LTL',
            ],
            'notifications_enabled' => ['nullable', 'boolean'],
            'email_notifications' => ['nullable', 'boolean'],
            'sms_notifications' => ['nullable', 'boolean'],
            'invoice_due_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:90',
            ],
            'auto_generate_invoices' => ['nullable', 'boolean'],
            'maintenance_mode' => ['nullable', 'boolean'],
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
            'app_name.string' => __('settings.validation.app_name.string'),
            'app_name.max' => __('settings.validation.app_name.max'),
            'app_name.regex' => __('settings.validation.app_name.regex'),
            'timezone.string' => __('settings.validation.timezone.string'),
            'timezone.in' => __('settings.validation.timezone.in'),
            'language.in' => __('settings.validation.language.in'),
            'date_format.in' => __('settings.validation.date_format.in'),
            'currency.size' => __('settings.validation.currency.size'),
            'currency.in' => __('settings.validation.currency.in'),
            'invoice_due_days.min' => __('settings.validation.invoice_due_days.min'),
            'invoice_due_days.max' => __('settings.validation.invoice_due_days.max'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'app_name' => __('settings.attributes.app_name'),
            'timezone' => __('settings.attributes.timezone'),
            'language' => __('settings.attributes.language'),
            'date_format' => __('settings.attributes.date_format'),
            'currency' => __('settings.attributes.currency'),
            'notifications_enabled' => __('settings.attributes.notifications_enabled'),
            'email_notifications' => __('settings.attributes.email_notifications'),
            'sms_notifications' => __('settings.attributes.sms_notifications'),
            'invoice_due_days' => __('settings.attributes.invoice_due_days'),
            'auto_generate_invoices' => __('settings.attributes.auto_generate_invoices'),
            'maintenance_mode' => __('settings.attributes.maintenance_mode'),
        ];
    }
}
