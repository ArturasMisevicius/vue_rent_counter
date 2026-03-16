<?php

return [
    'description' => 'Aprašymas',
    'system_page' => [
        'description' => 'Tvarkykite visos platformos nustatymus ir konfigūraciją. Pakeitimai įsigalios iš karto po išsaugojimo.',
        'notes' => [
            'backup_schedule' => 'Atsarginiame tvarkaraštyje naudojamas cron išraiškos formatas (pvz., "0 2 * * *" kasdien 2 val.)',
            'email' => 'Norint siųsti pranešimus, el. pašto nustatymams reikalingi galiojantys SMTP kredencialai',
            'feature_flags' => 'Funkcijų vėliavėlės turi įtakos visoms organizacijoms, nebent jos nepaisomos',
            'password_policy' => 'Slaptažodžių politikos pakeitimai taikomi tik naujiems slaptažodžiams',
            'queue' => 'Pakeitus eilės konfigūraciją, darbuotojui gali tekti paleisti iš naujo',
        ],
        'notes_title' => 'Konfigūracijos pastabos',
        'title' => 'Sistemos konfigūracija',
        'warning_body' => 'Sistemos nustatymų keitimas gali paveikti platformos stabilumą ir vartotojo patirtį. Visada pirmiausia išbandykite pakeitimus kūrimo aplinkoje. Prieš atlikdami reikšmingus pakeitimus, eksportuokite dabartinę konfigūraciją.',
        'warning_title' => 'Svarbus įspėjimas',
    ],
    'forms' => [
        'app_name' => 'Programos pavadinimas',
        'app_name_hint' => 'Programos pavadinimo patarimas',
        'save' => 'Išsaugoti',
        'timezone' => 'Laiko juosta',
        'timezone_hint' => 'Laiko juostos patarimas',
        'title' => 'Pavadinimas',
        'warnings' => [
            'backups' => 'Atsarginės kopijos',
            'env' => 'Env',
            'note_title' => 'Pastaba Pavadinimas',
        ],
    ],
    'maintenance' => [
        'clear_cache' => 'Išvalyti talpyklą',
        'clear_cache_description' => 'Išvalyti talpyklos aprašą',
        'run_backup' => 'Paleiskite atsarginę kopiją',
        'run_backup_description' => 'Vykdykite atsarginės kopijos aprašą',
        'title' => 'Pavadinimas',
    ],
    'stats' => [
        'cache_size' => 'Talpyklos dydis',
        'db_size' => 'Db dydis',
        'invoices' => 'Sąskaitos faktūros',
        'meters' => 'Skaitikliai',
        'properties' => 'Turtai',
        'users' => 'Vartotojai',
    ],
    'title' => 'Pavadinimas',
    'validation' => [
        'app_name' => [
            'max' => 'Programos pavadinimas negali būti ilgesnis nei 255 simboliai.',
            'string' => 'Programos pavadinimas turi būti eilutė.',
            'regex' => 'Programos pavadinime gali būti tik raidžių, skaičių, tarpų, brūkšnelių, apatinių brūkšnių ir taškų.',
        ],
        'timezone' => [
            'in' => 'Pasirinkta laiko juosta neteisinga.',
            'string' => 'Laiko juosta turi būti eilutė.',
        ],
        'language' => [
            'in' => 'Pasirinkta kalba nepalaikoma.',
        ],
        'date_format' => [
            'in' => 'Pasirinktas datos formatas netinkamas.',
        ],
        'currency' => [
            'size' => 'Valiutos kodą turi sudaryti tiksliai 3 simboliai.',
            'in' => 'Pasirinkta valiuta nepalaikoma.',
        ],
        'invoice_due_days' => [
            'min' => 'Sąskaitos faktūros apmokėjimo dienos turi būti bent 1 diena.',
            'max' => 'Sąskaitos faktūros apmokėjimo dienos negali būti ilgesnės nei 90 dienų.',
        ],
    ],
    'attributes' => [
        'app_name' => 'programos pavadinimas',
        'timezone' => 'laiko juosta',
        'language' => 'kalba',
        'date_format' => 'datos formatas',
        'currency' => 'valiuta',
        'notifications_enabled' => 'pranešimai įjungti',
        'email_notifications' => 'pašto pranešimus',
        'sms_notifications' => 'SMS pranešimai',
        'invoice_due_days' => 'sąskaitos faktūros apmokėjimo dienos',
        'auto_generate_invoices' => 'automatiškai generuoti sąskaitas faktūras',
        'maintenance_mode' => 'priežiūros režimas',
    ],
];
