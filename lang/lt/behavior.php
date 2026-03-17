<?php

return [
    'subscription' => [
        'actions' => [
            'manage' => 'Tvarkyti prenumeratą',
        ],
        'limit_blocked' => [
            'properties' => [
                'title' => 'Pasiektas objektų limitas',
                'body' => 'Dabartinis planas naudoja :used iš :limit objektų. Norėdami sukurti naują objektą, atnaujinkite prenumeratą.',
            ],
            'tenants' => [
                'title' => 'Pasiektas nuomininkų limitas',
                'body' => 'Dabartinis planas naudoja :used iš :limit nuomininkų. Norėdami sukurti naują nuomininką, atnaujinkite prenumeratą.',
            ],
        ],
        'grace_read_only' => [
            'title' => 'Reikalingas prenumeratos atnaujinimas',
            'body' => 'Organizacija yra lengvatiniame laikotarpyje iki :grace_ends_at. Duomenys lieka matomi, tačiau nauji pakeitimai blokuojami iki atnaujinimo.',
        ],
        'post_grace_read_only' => [
            'title' => 'Prenumerata pasibaigė',
            'body' => 'Lengvatinis laikotarpis baigėsi. Organizacijos duomenys vis dar matomi, tačiau keitimai nepasiekiami, kol prenumerata neatnaujinta.',
        ],
    ],
];
