<?php

declare(strict_types=1);

return [
    'validation' => [
        'report_type' => [
            'required' => 'Report type is required.',
            'in' => 'Report type must be one of consumption, revenue, outstanding, or meter-readings.',
        ],
        'format' => [
            'required' => 'Export format is required.',
            'in' => 'Export format must be csv, excel, or pdf.',
        ],
        'start_date' => [
            'date' => 'Start date must be a valid date.',
        ],
        'end_date' => [
            'date' => 'End date must be a valid date.',
            'after_or_equal' => 'End date must be after or the same as the start date.',
        ],
        'property_id' => [
            'exists' => 'The selected property does not exist.',
        ],
        'month' => [
            'date_format' => 'Month must be in YYYY-MM format.',
        ],
    ],
];
