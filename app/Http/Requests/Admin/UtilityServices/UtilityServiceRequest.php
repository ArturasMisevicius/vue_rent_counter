<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\UtilityServices;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\UnitOfMeasurement;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UtilityServiceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'unit_of_measurement' => ['nullable', Rule::enum(UnitOfMeasurement::class)],
            'default_pricing_model' => ['required', Rule::enum(PricingModel::class)],
            'service_type_bridge' => ['required', Rule::enum(ServiceType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'unit_of_measurement.enum' => ['enum', 'unit_of_measurement'],
            'default_pricing_model.required' => ['required', 'default_pricing_model'],
            'default_pricing_model.enum' => ['enum', 'default_pricing_model'],
            'service_type_bridge.required' => ['required', 'service_type_bridge'],
            'service_type_bridge.enum' => ['enum', 'service_type_bridge'],
            'description.string' => ['string', 'description'],
            'is_active.boolean' => ['boolean', 'is_active'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'name',
            'unit_of_measurement',
            'default_pricing_model',
            'service_type_bridge',
            'description',
            'is_active',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'unit_of_measurement',
            'default_pricing_model',
            'service_type_bridge',
            'description',
        ]);

        $this->emptyStringsToNull([
            'unit_of_measurement',
            'description',
        ]);

        $this->castBooleans([
            'is_active',
        ]);

        $unit = $this->input('unit_of_measurement');

        if ($unit instanceof UnitOfMeasurement) {
            $this->merge([
                'unit_of_measurement' => $unit->value,
            ]);
        }

        $pricingModel = $this->input('default_pricing_model');

        if ($pricingModel instanceof PricingModel) {
            $this->merge([
                'default_pricing_model' => $pricingModel->value,
            ]);
        }

        $serviceType = $this->input('service_type_bridge');

        if ($serviceType instanceof ServiceType) {
            $this->merge([
                'service_type_bridge' => $serviceType->value,
            ]);
        }
    }
}
