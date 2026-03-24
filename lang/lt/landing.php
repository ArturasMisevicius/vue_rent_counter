<?php

return [
    'brand' => [
        'tagline' => 'Nuomininkų ir komunalinių operacijų centras',
        'kicker' => 'Prieš prisijungimą',
    ],
    'hero' => [
        'eyebrow' => 'Viena platforma visiems turto vaidmenims',
        'title' => 'Valdykite nuomą, sąskaitas ir nuomininkų aptarnavimą iš vienos aiškios darbo erdvės.',
        'description' => 'Tenanto sujungia organizacijų įvedimą, pastatų ir objektų valdymą, skaitiklių bei sąskaitų procesus ir nuomininkų savitarną į vieną nuoseklų srautą. Šis viešas puslapis parodo, kaip veikia visas kelias dar prieš prisijungimą.',
        'chips' => [
            0 => 'Vaidmenimis grįstos darbo erdvės',
            1 => 'Lokalizuotas svečio ir prisijungimo kelias',
            2 => 'Skaitiklio iki sąskaitos operacinis srautas',
            3 => 'Nuomininkų savitarnos parengtis',
        ],
    ],
    'roles' => [
        'heading' => 'Vaidmenų darbo erdvės',
        'description' => 'Kiekvienas vaidmuo gauna aiškią atsakomybę, o bendros platformos taisyklės išlaiko visus perdavimus nuoseklius.',
        'items' => [
            0 => [
                'name' => 'Superadmin',
                'description' => 'Atsako už platformos būklę, globalų valdymą, prenumeratų politiką ir kalbų priežiūrą.',
            ],
            1 => [
                'name' => 'Admin',
                'description' => 'Konfigūruoja organizaciją, nustato operacinius standartus ir prižiūri atsiskaitymo parengtį.',
            ],
            2 => [
                'name' => 'Manager',
                'description' => 'Vykdo kasdienes pastatų operacijas, rodmenų procesus ir nuomininkų aptarnavimo užduotis.',
            ],
            3 => [
                'name' => 'Tenant',
                'description' => 'Naudojasi aiškiu portalu sąskaitoms, rodmenų pateikimui ir profilio atnaujinimui.',
            ],
        ],
    ],
    'tester' => [
        'heading' => 'Paleidimo kontrolinis sąrašas',
        'description' => 'Naudokite šį sąrašą viešai įėjimo patirčiai patikrinti prieš pereinant į autentifikuotus scenarijus.',
        'items' => [
            0 => 'Patikrinkite, ar Prisijungti ir Registruotis taškai aiškiai matomi visame puslapyje.',
            1 => 'Perjunkite kalbą ir įsitikinkite, kad struktūra bei CTA logika išlieka vienoda.',
            2 => 'Patvirtinkite, kad kiekviena vaidmens kortelė aiškiai nusako atsakomybių ribas.',
            3 => 'Peržiūrėkite gaires ir patikrinkite, kad planai atitinka realią produkto būseną.',
        ],
    ],
    'roadmap' => [
        'heading' => 'Operacinis planas',
        'lead' => 'Tenanto plečiama nuo viešo įėjimo iki pilnos vaidmenimis grįstos operacijų platformos.',
        'description' => 'Kiekviena plano kryptis uždaro aiškų tarpą tarp registracijos, atsiskaitymo, valdymo ir nuomininkų patirties.',
        'status' => 'Planuojama',
        'items' => [
            0 => [
                'title' => 'Bendras autentifikuotas karkasas',
                'description' => 'Nuoseklus apvalkalas navigacijai, kalbai, pranešimams ir paskyros kontekstui visiems vaidmenims.',
            ],
            1 => [
                'title' => 'Platformos valdymo sluoksnis',
                'description' => 'Superadmin įrankiai organizacijoms, prenumeratoms, vertimų valdymui ir saugumo stebėsenai.',
            ],
            2 => [
                'title' => 'Organizacijos operacijų branduolys',
                'description' => 'Admin ir manager darbo eiga pastatams, objektams, tiekėjams, skaitikliams, sąskaitoms ir ataskaitoms.',
            ],
            3 => [
                'title' => 'Nuomininkų portalas',
                'description' => 'Mobilumui pritaikyta sritis sąskaitoms, rodmenų pateikimui, profiliui ir objekto kontekstui.',
            ],
            4 => [
                'title' => 'Skersinės platformos taisyklės',
                'description' => 'Bendras validavimas, politikų taikymas ir prenumeratų ribos, užtikrinančios patikimą kiekvieno vaidmens kelią.',
            ],
        ],
    ],
    'cta' => [
        'heading' => 'Pasirinkite tinkamą įėjimo tašką',
        'description' => 'Prisijunkite, jei jau dirbate sistemoje, arba užregistruokite Admin paskyrą naujos organizacijos paleidimui.',
        'note' => 'Manager ir Tenant prieiga išlieka kvietimų pagrindu, kad būtų užtikrintas valdomas įvedimas ir izoliacija.',
        'login' => 'Prisijungti',
        'register' => 'Registruotis',
    ],
];
