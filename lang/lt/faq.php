<?php

declare(strict_types=1);

return [
    'labels' => [
        'resource' => 'DUK',
        'question' => 'Klausimas',
        'category' => 'Kategorija',
        'answer' => 'Atsakymas',
        'display_order' => 'Rodymo eilė',
        'published' => 'Publikuota',
        'status' => 'Būsena',
        'order' => 'Eilė',
        'last_updated' => 'Atnaujinta',
        'details' => 'DUK įrašas',
    ],

    'sections' => [
        'faq_entry' => 'DUK įrašas',
    ],

    'placeholders' => [
        'question' => 'Koks atsiskaitymo ciklas?',
        'category' => 'Atsiskaitymas, prieiga, skaitikliai...',
    ],

    'helper_text' => [
        'entry' => 'Kurti ar redaguoti DUK įrašus, rodomus viešame puslapyje',
        'category' => 'Pasirinktinė kategorija susijusiems klausimams grupuoti',
        'answer' => 'Rašykite glaustus, išsamius atsakymus. Turinys rodomas viešame puslapyje.',
        'order' => 'Mažesni numeriai rodomi pirmi.',
        'published' => 'Tik publikuoti DUK rodomi viešame puslapyje',
        'visible' => 'Matoma viešame puslapyje',
        'hidden' => 'Nerodoma viešai',
    ],

    'filters' => [
        'status' => 'Būsena',
        'category' => 'Kategorija',
        'options' => [
            'published' => 'Publikuota',
            'draft' => 'Juodraštis',
        ],
    ],

    'empty' => [
        'heading' => 'DUK įrašų nėra',
        'description' => 'Sukurkite pirmą DUK įrašą, kad padėtumėte vartotojams.',
    ],

    'actions' => [
        'add_first' => 'Pridėti pirmą DUK',
    ],

    'modals' => [
        'delete' => [
            'heading' => 'Ištrinti DUK įrašus',
            'description' => 'Ar tikrai norite ištrinti šiuos DUK įrašus? Šio veiksmo nebus galima atšaukti.',
        ],
    ],
];
