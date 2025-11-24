<?php

declare(strict_types=1);

return [
    'errors_occurred' => 'Please correct the following errors:',
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'numeric' => 'The :attribute may not be greater than :max.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'integer' => 'The :attribute must be an integer.',
    'numeric' => 'The :attribute must be a number.',
    'email' => 'The :attribute must be a valid email address.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'unique' => 'The :attribute has already been taken.',
    'in' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute does not exist.',
    'date' => 'The :attribute must be a valid date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'after' => 'The :attribute must be a date after :date.',
    'array' => 'The :attribute must be an array.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'boolean' => 'The :attribute field must be true or false.',
    'enum' => 'The selected :attribute is invalid.',

    'attributes' => [],
];
