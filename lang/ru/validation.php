<?php

declare(strict_types=1);

return [
    'errors_occurred' => 'Пожалуйста, исправьте следующие ошибки:',
    'required' => 'Поле :attribute является обязательным.',
    'string' => 'Поле :attribute должно быть строкой.',
    'max' => [
        'string' => 'Поле :attribute не может быть больше :max символов.',
        'numeric' => 'Поле :attribute не может быть больше :max.',
    ],
    'min' => [
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
        'numeric' => 'Поле :attribute должно быть не меньше :min.',
    ],
    'integer' => 'Поле :attribute должно быть целым числом.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'email' => 'Поле :attribute должно быть корректным email адресом.',
    'confirmed' => 'Подтверждение для :attribute не совпадает.',
    'unique' => 'Поле :attribute уже занято.',
    'in' => 'Выбранное значение :attribute недопустимо.',
    'exists' => 'Выбранное значение поля :attribute не существует.',
    'date' => 'Поле :attribute должно быть корректной датой.',
    'before_or_equal' => 'Поле :attribute должно быть датой не позже :date.',
    'after' => 'Поле :attribute должно быть датой после :date.',
    'array' => 'Поле :attribute должно быть массивом.',
    'required_if' => 'Поле :attribute обязательно, когда :other имеет значение :value.',
    'required_with' => 'Поле :attribute обязательно, когда указано :values.',
    'boolean' => 'Поле :attribute должно быть булевым значением.',
    'enum' => 'Выбранное значение :attribute недопустимо.',

    'attributes' => [],
];
