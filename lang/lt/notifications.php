<?php

declare(strict_types=1);

return [
    'subscription_expiry' => [
        'subject' => 'Prenumeratos galiojimo įspėjimas',
        'greeting' => 'Sveiki, :name!',
        'intro' => 'Jūsų prenumerata Vilnius Utilities Billing System baigsis po **:days d.** :date.',
        'plan' => '**Dabartinis planas:** :plan',
        'properties' => '**Objektai:** :used / :max',
        'tenants' => '**Nuomininkai:** :used / :max',
        'cta_intro' => 'Kad išvengtumėte paslaugos sustabdymo, pratęskite prenumeratą prieš jai pasibaigiant.',
        'cta_notice' => 'Pasibaigus galiojimui, jūsų paskyra bus tik skaitymo režime, kol nepratęsite.',
        'action' => 'Pratęsti prenumeratą',
        'support' => 'Jei turite klausimų dėl pratęsimo, susisiekite su palaikymu.',
    ],

    'welcome' => [
        'subject' => 'Sveiki atvykę į Vilnius Utilities Billing System',
        'greeting' => 'Sveiki, :name!',
        'account_created' => 'Jūsų nuomininko paskyra sukurta šiam objektui:',
        'address' => '**Adresas:** :address',
        'property_type' => '**Objekto tipas:** :type',
        'credentials_heading' => '**Prisijungimo duomenys:**',
        'email' => 'El. paštas: :email',
        'temporary_password' => 'Laikinas slaptažodis: :password',
        'password_reminder' => 'Prisijunkite ir nedelsdami pakeiskite slaptažodį.',
        'action' => 'Prisijungti',
        'support' => 'Jei turite klausimų, susisiekite su savo objekto administratoriumi.',
    ],

    'tenant_reassigned' => [
        'subject' => 'Objekto priskyrimas atnaujintas',
        'greeting' => 'Sveiki, :name!',
        'updated' => 'Jūsų objekto priskyrimas buvo atnaujintas.',
        'previous' => '**Ankstesnis objektas:** :address',
        'new' => '**Naujas objektas:** :address',
        'assigned' => 'Jums priskirtas objektas:',
        'property' => '**Objektas:** :address',
        'property_type' => '**Objekto tipas:** :type',
        'view_dashboard' => 'Peržiūrėti skydelį',
        'info' => 'Dabar galite matyti šio objekto komunalinę informaciją.',
        'support' => 'Jei turite klausimų, susisiekite su objekto administratoriumi.',
    ],

    'meter_reading_submitted' => [
        'subject' => 'Pateiktas naujas skaitiklio rodmuo',
        'greeting' => 'Sveiki, :name!',
        'submitted_by' => 'Naują rodmenį pateikė **:tenant**.',
        'details' => '**Rodmens detalės:**',
        'property' => 'Objektas: :address',
        'meter_type' => 'Skaitiklio tipas: :type',
        'serial' => 'Serijos numeris: :serial',
        'reading_date' => 'Rodmens data: :date',
        'reading_value' => 'Rodmens reikšmė: :value',
        'zone' => 'Zona: :zone',
        'consumption' => 'Suvartojimas: :consumption',
        'view' => 'Peržiūrėti rodmenis',
        'manage_hint' => 'Visus rodmenis galite peržiūrėti ir valdyti savo skydelyje.',
    ],

    'overdue_invoice' => [
        'subject' => 'Sąskaita #:id yra pavėluota',
        'greeting' => 'Sveiki, :name,',
        'overdue' => 'Sąskaita #:id yra pavėluota.',
        'amount' => 'Bendra suma: :amount',
        'due_date' => 'Apmokėjimo data: :date',
        'pay_notice' => 'Prašome apmokėti sąskaitą kuo greičiau, kad išvengtumėte paslaugų sutrikimų.',
        'action' => 'Peržiūrėti sąskaitą',
        'ignore' => 'Jei jau apmokėjote, ignoruokite šį laišką.',
    ],

    'profile' => [
        'updated' => 'Profilis sėkmingai atnaujintas.',
        'password_updated' => 'Slaptažodis sėkmingai atnaujintas.',
    ],
];
