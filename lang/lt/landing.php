<?php

declare(strict_types=1);

return [
    'cta_bar' => [
        'eyebrow' => 'Komunalinių paslaugų valdymas',
        'title' => 'Supaprastinkite savo nekilnojamojo turto veiklą',
    ],
    'dashboard' => [
        'draft_invoices' => 'Sąskaitų faktūrų projektai',
        'draft_invoices_hint' => 'Sąskaitos faktūros, laukiančios patvirtinimo',
        'electricity' => 'Elektra',
        'electricity_status' => 'Elektros sistemos būsena',
        'healthy' => 'Sveika',
        'heating' => 'Šildymas',
        'heating_status' => 'Šildymo sistemos būsena',
        'live_overview' => 'Tiesioginė sistemos apžvalga',
        'meters_validated' => 'Skaitikliai patvirtinti',
        'meters_validated_hint' => 'Skaitikliai su patvirtintais rodmenimis',
        'portfolio_health' => 'Portfelio sveikata',
        'recent_readings' => 'Naujausi skaitiklių rodmenys',
        'trusted' => 'Patikimas',
        'water' => 'Vanduo',
        'water_status' => 'Vandens sistemos būsena',
    ],
    'faq_intro' => 'Dažnai užduodami klausimai apie mūsų komunalinių paslaugų valdymo platformą',
    'faq_section' => [
        'category_prefix' => 'Kategorija:',
        'eyebrow' => 'Pagalba',
        'title' => 'Dažnai užduodami klausimai',
    ],
    'features_subtitle' => 'Viskas, ko reikia efektyviam komunalinių paslaugų valdymui',
    'features_title' => 'Išsamus komunalinių paslaugų valdymas',
    'hero' => [
        'badge' => 'Vilniaus komunalinių paslaugų platforma',
        'tagline' => 'Valdykite nekilnojamąjį turtą, skaitiklius ir sąskaitas faktūras su pasitikėjimu',
        'title' => 'Šiuolaikiškas komunalinių paslaugų valdymas Lietuvos nekilnojamajam turtui',
    ],
    'metric_values' => [
        'five_minutes' => '< 5 minutės',
        'full' => '100%',
        'zero' => '0',
    ],
    'metrics' => [
        'cache' => 'Talpyklos našumas',
        'isolation' => 'Nuomotojų izoliacija',
        'readings' => 'Skaitiklių rodmenys',
    ],
    'features' => [
        'unified_metering' => [
            'title' => 'Suvienijtas skaitiklių valdymas',
            'description' => 'Valdykite visus elektros, vandens ir šildymo skaitiklius vienoje vietoje su automatizuotu rodmenų patvirtinimu.',
        ],
        'accurate_invoicing' => [
            'title' => 'Tikslūs sąskaitų faktūrų skaičiavimai',
            'description' => 'Automatiškai generuokite sąskaitas faktūras pagal patvirtintus skaitiklių rodmenis su tarifikų momentinėmis nuotraukomis.',
        ],
        'role_access' => [
            'title' => 'Vaidmenų prieigos kontrolė',
            'description' => 'Saugus daugiašalis prieigos valdymas superadministratoriams, valdytojams ir nuomotojams.',
        ],
        'reporting' => [
            'title' => 'Išsamūs ataskaitos',
            'description' => 'Generuokite išsamias ataskaitas apie suvartojimą, pajamas ir portfelio našumą.',
        ],
        'performance' => [
            'title' => 'Aukštas našumas',
            'description' => 'Optimizuota architektūra su talpyklos mechanizmais ir N+1 užklausų prevencija.',
        ],
        'tenant_clarity' => [
            'title' => 'Nuomotojų skaidrumas',
            'description' => 'Nuomotojai gali peržiūrėti savo skaitiklių rodmenis, sąskaitas faktūras ir atsisiųsti PDF failus.',
        ],
    ],
    'faq' => [
        'validation' => [
            'question' => 'Kaip veikia skaitiklių rodmenų patvirtinimas?',
            'answer' => 'Visi skaitiklių rodmenys yra patvirtinami monotoniškumo ir laiko taisyklėmis. Sistema automatiškai aptinka anomalijas ir reikalauja vadybininko patvirtinimo.',
        ],
        'tenants' => [
            'question' => 'Ką gali matyti nuomotojai?',
            'answer' => 'Nuomotojai gali peržiūrėti savo nekilnojamojo turto informaciją, skaitiklių rodmenis, sąskaitų faktūrų istoriją ir atsisiųsti PDF failus. Jie negali matyti kitų nuomotojų duomenų.',
        ],
        'invoices' => [
            'question' => 'Kaip veikia sąskaitų faktūrų generavimas?',
            'answer' => 'Sąskaitos faktūros generuojamos automatiškai pagal patvirtintus skaitiklių rodmenis. Tarifikų momentinės nuotraukos užtikrina, kad sąskaitų faktūrų skaičiavimai išlieka tikslūs net keičiantis tarifams.',
        ],
        'security' => [
            'question' => 'Kaip užtikrinamas duomenų saugumas?',
            'answer' => 'Platforma naudoja daugiašalę nuomotojų izoliaciją, vaidmenų prieigos kontrolę ir išsamų auditą. Visi duomenys yra šifruojami ir reguliariai kuriamos atsarginės kopijos.',
        ],
        'support' => [
            'question' => 'Kokia pagalba teikiama?',
            'answer' => 'Teikiame išsamią dokumentaciją, mokymus ir techninę pagalbą. Platforma palaiko lietuvių ir anglų kalbas su lokalizuotomis sąsajomis.',
        ],
    ],
];