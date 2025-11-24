<?php

declare(strict_types=1);

return [
    'validation' => [
        'report_type' => [
            'required' => 'Тип отчета обязателен.',
            'in' => 'Тип отчета должен быть одним из: consumption, revenue, outstanding или meter-readings.',
        ],
        'format' => [
            'required' => 'Формат экспорта обязателен.',
            'in' => 'Формат экспорта должен быть csv, excel или pdf.',
        ],
        'start_date' => [
            'date' => 'Дата начала должна быть корректной датой.',
        ],
        'end_date' => [
            'date' => 'Дата окончания должна быть корректной датой.',
            'after_or_equal' => 'Дата окончания должна быть не раньше даты начала.',
        ],
        'property_id' => [
            'exists' => 'Выбранный объект не существует.',
        ],
        'month' => [
            'date_format' => 'Месяц должен быть в формате YYYY-MM.',
        ],
    ],
];
