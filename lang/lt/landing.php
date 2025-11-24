<?php

return [
    'hero' => [
        'title' => 'Nuomos skaitiklis suderina rodmenis, sąskaitas ir nuomininkus.',
        'tagline' => 'Visos komandos dalijasi viena tiesa apie objektus, tarifus ir skaitiklių rodmenis.',
        'badge' => 'Komunaliniai · Sąskaitos · Atitiktis',
    ],
    'metrics' => [
        'cache' => 'Valdymo skydelio podėlio atnaujinimas',
        'readings' => 'Nevaliduotų rodmenų gamyboje',
        'isolation' => 'Rolėmis grįstas nuomininkų atskyrimas',
    ],
    'features_title' => 'Funkcijos, sukurtos komunalinių paslaugų sąskaitoms',
    'features_subtitle' => 'Kodėl komandos išlieka suderintos',
    'cta_bar' => [
        'title' => 'Prisijunkite arba registruokitės.',
        'eyebrow' => 'Pasiruošę suderinti atsiskaitymus?',
    ],
    'faq_intro' => 'Sukūrėme Nuomos skaitiklį, kad valdytojai, finansai ir nuomininkai jaustųsi ramiai. Čia dažniausi klausimai.',
    'features' => [
        'unified_metering' => [
            'title' => 'Vieninga apskaita',
            'description' => 'Rinkite elektros, vandens ir šildymo rodmenis vienoje vietoje su automatine validacija ir anomalijų tikrinimu.',
        ],
        'accurate_invoicing' => [
            'title' => 'Tikslios sąskaitos',
            'description' => 'Kurti detalias sąskaitas su tarifų versijomis, zonomis ir mokesčiais, išlaikant sinchroną su nuomininkais ir vadybininkais.',
        ],
        'role_access' => [
            'title' => 'Prieiga pagal roles',
            'description' => 'Valdymo skydeliai administratoriui, vadybininkui ir nuomininkui su politika ir auditais.',
        ],
        'reporting' => [
            'title' => 'Ataskaitos, kurios informuoja',
            'description' => 'Vartojimo, pajamų ir atitikties ataskaitos su filtrais, eksportu ir puslapiavimu.',
        ],
        'performance' => [
            'title' => 'Pirmiausia našumas',
            'description' => 'Optimizuotos užklausos, eager loading ir podėlis, kad dideli portfeliai veiktų greitai.',
        ],
        'tenant_clarity' => [
            'title' => 'Aiškumas nuomininkui',
            'description' => 'Savitarna nuomininkams su sąskaitų detalėmis, tendencijomis ir filtravimu pagal objektą.',
        ],
    ],
    'faq' => [
        'validation' => [
            'question' => 'Kaip validuojami skaitiklių rodmenys?',
            'answer' => 'Rodmenys tikrinami dėl monotonijos, zonų atitikimo ir anomalijų prieš sąskaitų išrašymą.',
        ],
        'tenants' => [
            'question' => 'Ar nuomininkai mato tik savo objektus?',
            'answer' => 'Taip, pritaikytas TenantScope ir politikos, kad vartotojai matytų tik priskirtus duomenis.',
        ],
        'invoices' => [
            'question' => 'Ar sąskaitos palaiko versijas ir korekcijas?',
            'answer' => 'Sąskaitos turi juodraščius, patvirtinimo laiką ir korekcijų auditą, kad istorija liktų nekintama.',
        ],
        'admin' => [
            'question' => 'Ar yra administratoriaus valdymo panelė?',
            'answer' => 'Administratoriai valdo vartotojus, tiekėjus, tarifus ir auditą per Filament skydą su rolėmis.',
        ],
    ],
    'dashboard' => [
        'live_overview' => 'Tiesioginė apžvalga',
        'portfolio_health' => 'Portfelio būklė',
        'healthy' => 'Sveika',
        'draft_invoices' => 'Sąskaitų juodraščiai',
        'draft_invoices_hint' => 'Paruoštos patvirtinti',
        'meters_validated' => 'Patvirtinti skaitikliai',
        'meters_validated_hint' => 'Visose zonose',
        'recent_readings' => 'Naujausi rodmenys',
        'water' => 'Vanduo',
        'water_status' => 'Monotoniškumas ✓',
        'electricity' => 'Elektra',
        'electricity_status' => 'Anomalijų patikra ✓',
        'heating' => 'Šildymas',
        'heating_status' => 'Zonų padalijimas ✓',
        'trusted' => 'Patikima',
    ],
    'faq_section' => [
        'eyebrow' => 'Reikalingi atsakymai',
        'title' => 'DUK',
        'category_prefix' => 'Kategorija:',
    ],
    'metric_values' => [
        'five_minutes' => '5 min',
        'zero' => 'Nulis',
        'full' => '100%',
    ],
];
