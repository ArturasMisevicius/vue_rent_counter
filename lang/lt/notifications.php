<?php

declare(strict_types=1);

return [
    'invoice' => [
        'created' => 'Sąskaita sėkmingai sukurta.',
        'updated' => 'Sąskaita sėkmingai atnaujinta.',
        'deleted' => 'Sąskaita sėkmingai ištrinta.',
        'finalized' => 'Sąskaita sėkmingai patvirtinta.',
        'finalized_locked' => 'Sąskaita sėkmingai patvirtinta. Dabar ji nebekeičiama.',
        'marked_paid' => 'Sąskaita pažymėta kaip apmokėta.',
        'sent' => 'Sąskaita sėkmingai išsiųsta.',
        'generated_bulk' => 'Sėkmingai sugeneruota :count sąskaitų.',
    ],

    'invoice_item' => [
        'created' => 'Sąskaitos eilutė sėkmingai pridėta.',
        'updated' => 'Sąskaitos eilutė sėkmingai atnaujinta.',
        'deleted' => 'Sąskaitos eilutė sėkmingai pašalinta.',
    ],

    'meter' => [
        'created' => 'Skaitiklis sėkmingai sukurtas.',
        'updated' => 'Skaitiklis sėkmingai atnaujintas.',
        'deleted' => 'Skaitiklis sėkmingai ištrintas.',
    ],

    'meter_reading' => [
        'created' => 'Skaitiklio rodmuo sėkmingai sukurtas.',
        'updated' => 'Skaitiklio rodmuo sėkmingai atnaujintas.',
        'deleted' => 'Skaitiklio rodmuo sėkmingai ištrintas.',
        'bulk_created' => 'Masiniai rodmenys sėkmingai sukurti.',
        'corrected' => 'Skaitiklio rodmuo pataisytas. Sukurtas audito įrašas.',
    ],

    'building' => [
        'created' => 'Pastatas sėkmingai sukurtas.',
        'updated' => 'Pastatas sėkmingai atnaujintas.',
        'deleted' => 'Pastatas sėkmingai ištrintas.',
        'gyvatukas' => 'Gyvatukas apskaičiuotas: :average kWh',
        'gyvatukas_summer' => 'Gyvatukas vasaros vidurkis apskaičiuotas: :average kWh',
    ],

    'subscription' => [
        'updated' => 'Prenumerata sėkmingai atnaujinta.',
        'renewed' => 'Prenumerata sėkmingai pratęsta.',
        'suspended' => 'Prenumerata sėkmingai sustabdyta.',
        'cancelled' => 'Prenumerata sėkmingai atšaukta.',
    ],

    'organization' => [
        'created' => 'Organizacija sėkmingai sukurta.',
        'updated' => 'Organizacija sėkmingai atnaujinta.',
        'deactivated' => 'Organizacija sėkmingai deaktyvuota.',
        'reactivated' => 'Organizacija sėkmingai atkurta.',
    ],

    'tenant' => [
        'created' => 'Nuomininkas sėkmingai sukurtas.',
        'updated' => 'Nuomininkas sėkmingai atnaujintas.',
        'deleted' => 'Nuomininkas sėkmingai ištrintas.',
        'invoice_sent' => 'Sąskaita sėkmingai išsiųsta.',
    ],

    'admin_tenant' => [
        'created' => 'Nuomininko paskyra sėkmingai sukurta. Siunčiamas pasveikinimo laiškas.',
        'updated' => 'Nuomininko paskyra sėkmingai atnaujinta.',
        'deactivated' => 'Nuomininko paskyra sėkmingai deaktyvuota.',
        'reactivated' => 'Nuomininko paskyra sėkmingai aktyvuota.',
        'reassigned' => 'Nuomininkas sėkmingai priskirtas iš naujo. Išsiųstas pranešimo el. laiškas.',
    ],

    'property' => [
        'created' => 'Objektas sėkmingai sukurtas.',
        'updated' => 'Objektas sėkmingai atnaujintas.',
        'deleted' => 'Objektas sėkmingai ištrintas.',
    ],

    'user' => [
        'created' => 'Vartotojas sėkmingai sukurtas.',
        'updated' => 'Vartotojas sėkmingai atnaujintas.',
        'deleted' => 'Vartotojas sėkmingai ištrintas.',
    ],

    'profile' => [
        'updated' => 'Profilis sėkmingai atnaujintas.',
        'password_updated' => 'Slaptažodis sėkmingai atnaujintas.',
    ],

    'settings' => [
        'updated' => 'Nustatymai sėkmingai atnaujinti. Kai kuriems pakeitimams gali reikėti atnaujinti .env ir perleisti programą.',
        'backup_completed' => 'Atsarginė kopija sėkmingai sukurta.',
        'cache_cleared' => 'Podėliai sėkmingai išvalyti.',
        'backup_failed' => 'Atsarginė kopija nepavyko: :message',
        'cache_failed' => 'Nepavyko išvalyti podėlio: :message',
    ],

    'tariff' => [
        'created' => 'Tarifas sėkmingai sukurtas.',
        'version_created' => 'Nauja tarifo versija sėkmingai sukurta.',
        'updated' => 'Tarifas sėkmingai atnaujintas.',
        'deleted' => 'Tarifas sėkmingai ištrintas.',
    ],

    'account' => [
        'updated' => 'Prenumerata sėkmingai atnaujinta.',
    ],
];
