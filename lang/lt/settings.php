<?php

declare(strict_types=1);

return [
    'description' => 'Aprašymas',
    'forms' => [
        'app_name' => 'Programos pavadinimas',
        'app_name_hint' => 'Programos pavadinimo patarimas',
        'save' => 'Išsaugoti',
        'timezone' => 'Laiko juosta',
        'timezone_hint' => 'Laiko juostos patarimas',
        'title' => 'Pavadinimas',
        'warnings' => [
            'backups' => 'Atsarginės kopijos',
            'env' => 'Aplinka',
            'note_title' => 'Pastabos pavadinimas',
        ],
    ],
    'maintenance' => [
        'clear_cache' => 'Išvalyti talpyklą',
        'clear_cache_description' => 'Išvalyti talpyklos aprašymą',
        'run_backup' => 'Paleisti atsarginę kopiją',
        'run_backup_description' => 'Paleisti atsarginės kopijos aprašymą',
        'title' => 'Pavadinimas',
    ],
    'stats' => [
        'cache_size' => 'Talpyklos dydis',
        'db_size' => 'Duomenų bazės dydis',
        'invoices' => 'Sąskaitos faktūros',
        'meters' => 'Skaitikliai',
        'properties' => 'Nuosavybės',
        'users' => 'Vartotojai',
    ],
    'title' => 'Pavadinimas',
    'validation' => [
        'app_name' => [
            'max' => 'Programos pavadinimas negali būti ilgesnis nei 255 simboliai.',
            'string' => 'Programos pavadinimas turi būti tekstas.',
            'regex' => 'Programos pavadinime gali būti tik raidės, skaičiai, tarpai, brūkšneliai, pabraukimai ir taškai.',
        ],
        'timezone' => [
            'in' => 'Pasirinkta laiko juosta yra netinkama.',
            'string' => 'Laiko juosta turi būti tekstas.',
        ],
        'language' => [
            'in' => 'Pasirinkta kalba nepalaikoma.',
        ],
        'date_format' => [
            'in' => 'Pasirinktas datos formatas yra netinkamas.',
        ],
        'currency' => [
            'size' => 'Valiutos kodas turi būti lygiai 3 simboliai.',
            'in' => 'Pasirinkta valiuta nepalaikoma.',
        ],
        'invoice_due_days' => [
            'min' => 'Sąskaitos faktūros mokėjimo terminas turi būti bent 1 diena.',
            'max' => 'Sąskaitos faktūros mokėjimo terminas negali būti ilgesnis nei 90 dienų.',
        ],
    ],
    'attributes' => [
        'app_name' => 'programos pavadinimas',
        'timezone' => 'laiko juosta',
        'language' => 'kalba',
        'date_format' => 'datos formatas',
        'currency' => 'valiuta',
        'notifications_enabled' => 'pranešimai įjungti',
        'email_notifications' => 'el. pašto pranešimai',
        'sms_notifications' => 'SMS pranešimai',
        'invoice_due_days' => 'sąskaitos faktūros mokėjimo terminas',
        'auto_generate_invoices' => 'automatiškai generuoti sąskaitas faktūras',
        'maintenance_mode' => 'priežiūros režimas',
    ],
];
