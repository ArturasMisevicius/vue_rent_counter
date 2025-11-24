<?php

declare(strict_types=1);

return [
    'validation' => [
        'locale' => [
            'required' => 'Locale is required.',
            'string' => 'Locale must be text.',
            'max' => 'Locale may not exceed 5 characters.',
        ],
    ],
];
