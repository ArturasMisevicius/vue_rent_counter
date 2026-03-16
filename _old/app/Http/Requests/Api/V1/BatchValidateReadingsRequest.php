<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for batch meter reading validation API.
 */
class BatchValidateReadingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\MeterReading::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reading_ids' => [
                'required',
                'array',
                'min:1',
                'max:' . config('service_validation.performance.max_batch_size', 500),
            ],
            'reading_ids.*' => [
                'integer',
            ],
            'validation_options' => 'sometimes|array',
            'validation_options.parallel_processing' => 'boolean',
            'validation_options.include_performance_metrics' => 'boolean',
            'validation_options.stop_on_first_error' => 'boolean',
            'validation_options.skip_seasonal_validation' => 'boolean',
            'validation_options.strict_mode' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reading_ids.required' => __('validation.reading_ids_required'),
            'reading_ids.array' => __('validation.reading_ids_must_be_array'),
            'reading_ids.min' => __('validation.reading_ids_minimum_one'),
            'reading_ids.max' => __('validation.reading_ids_maximum_exceeded', [
                'max' => config('service_validation.performance.max_batch_size', 500)
            ]),
            'reading_ids.*.integer' => __('validation.reading_id_must_be_integer'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reading_ids' => __('validation.attributes.reading_ids'),
            'reading_ids.*' => __('validation.attributes.reading_id'),
            'validation_options' => __('validation.attributes.validation_options'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $readingIds = $this->input('reading_ids', []);
            
            // Check for duplicate IDs
            if (count($readingIds) !== count(array_unique($readingIds))) {
                $validator->errors()->add(
                    'reading_ids',
                    __('validation.duplicate_reading_ids_not_allowed')
                );
            }

            // Validate batch size against performance limits
            $batchSize = count($readingIds);
            $optimalBatchSize = config('service_validation.performance.batch_validation_size', 100);
            
            if ($batchSize > $optimalBatchSize) {
                // Add warning but don't fail validation
                $this->merge([
                    '_performance_warning' => __('validation.batch_size_exceeds_optimal', [
                        'current' => $batchSize,
                        'optimal' => $optimalBatchSize
                    ])
                ]);
            }
        });
    }

    /**
     * Get the validated data with additional processing.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        // Ensure reading_ids are unique integers
        if (isset($validated['reading_ids'])) {
            $validated['reading_ids'] = array_unique(
                array_map('intval', $validated['reading_ids'])
            );
        }

        // Set default validation options
        $validated['validation_options'] = array_merge([
            'parallel_processing' => true,
            'include_performance_metrics' => true,
            'stop_on_first_error' => false,
        ], $validated['validation_options'] ?? []);

        return $validated;
    }
}
