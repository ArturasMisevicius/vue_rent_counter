<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Properties;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Rules\WithinPropertyLimit;
use App\Services\SubscriptionChecker;

class StorePropertyRequest extends PropertyRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return parent::authorize();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'subscription_limit' => [new WithinPropertyLimit(app(SubscriptionChecker::class))],
        ];
    }

    public function messages(): array
    {
        return parent::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'subscription_limit' => $this->translateAttribute('property'),
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'subscription_limit' => true,
        ]);
    }
}
