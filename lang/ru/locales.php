<?php

declare(strict_types=1);

return [
    'validation' => [
        'locale' => [
            'required' => 'Необходимо выбрать язык.',
            'string' => 'Код языка должен быть текстовым.',
            'max' => 'Код языка не может превышать 5 символов.',
        ],
    ],
];
