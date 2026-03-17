<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tariffs;

use App\Enums\TariffType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TariffRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
    }

    public function forOrganization(?int $organizationId): self
    {
        $request = clone $this;
        $request->organizationId = $organizationId;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_id' => [
                'required',
                'integer',
                Rule::exists('providers', 'id')->where(
                    fn ($query) => $query->where('organization_id', $this->organizationId),
                ),
            ],
            'remote_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'configuration.type' => ['required', Rule::enum(TariffType::class)],
            'configuration.currency' => ['required', 'string', 'max:10'],
            'configuration.rate' => [
                Rule::requiredIf(fn (): bool => $this->input('configuration.type') === TariffType::FLAT->value),
                'nullable',
                'numeric',
                'min:0',
            ],
            'active_from' => ['required', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'provider_id.required' => ['required', 'provider'],
            'provider_id.integer' => ['integer', 'provider'],
            'provider_id.exists' => ['exists', 'provider'],
            'remote_id.max' => ['max.string', 'remote_id', ['max' => 255]],
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'configuration.type.required' => ['required', 'configuration_type'],
            'configuration.type.enum' => ['enum', 'configuration_type'],
            'configuration.currency.required' => ['required', 'configuration_currency'],
            'configuration.currency.max' => ['max.string', 'configuration_currency', ['max' => 10]],
            'configuration.rate.required' => ['required', 'configuration_rate'],
            'configuration.rate.numeric' => ['numeric', 'configuration_rate'],
            'configuration.rate.min' => ['min.numeric', 'configuration_rate', ['min' => 0]],
            'active_from.required' => ['required', 'active_from'],
            'active_from.date' => ['date', 'active_from'],
            'active_until.date' => ['date', 'active_until'],
            'active_until.after_or_equal' => ['after_or_equal', 'active_until', ['date' => $this->translateAttribute('active_from')]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'provider_id' => $this->translateAttribute('provider'),
            'configuration.type' => $this->translateAttribute('configuration_type'),
            'configuration.currency' => $this->translateAttribute('configuration_currency'),
            'configuration.rate' => $this->translateAttribute('configuration_rate'),
            ...$this->translatedAttributes([
                'remote_id',
                'name',
                'active_from',
                'active_until',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'provider_id',
            'remote_id',
            'name',
            'configuration.type',
            'configuration.currency',
            'configuration.rate',
            'active_from',
            'active_until',
        ]);

        $this->emptyStringsToNull([
            'remote_id',
            'configuration.rate',
            'active_until',
        ]);

        $type = data_get($this->all(), 'configuration.type');

        if ($type instanceof TariffType) {
            $input = $this->all();
            data_set($input, 'configuration.type', $type->value);
            $this->replace($input);
        }
    }
}
