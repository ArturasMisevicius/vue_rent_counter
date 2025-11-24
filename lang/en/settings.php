<?php

declare(strict_types=1);

return [
    'validation' => [
        'app_name' => [
            'string' => 'Application name must be text.',
            'max' => 'Application name may not exceed 255 characters.',
        ],
        'timezone' => [
            'string' => 'Timezone must be text.',
            'in' => 'Timezone must be either Europe/Vilnius or UTC.',
        ],
    ],
];
