<?php

return [
    'errors' => [
        'cross_tenant_access_denied' => 'Neturite leidimo pasiekti šio nuomininko.',
        'tenant_inactive' => 'Nuomininko paskyra neaktyvi.',
        'authentication_required' => 'Šiai operacijai reikalingas autentifikavimas.',
        'rate_limit_user' => 'Viršijote sąskaitų generavimo limitą. Bandykite dar kartą po valandos.',
        'rate_limit_tenant' => 'Nuomininkas viršijo sąskaitų generavimo limitą. Bandykite vėliau.',
        'duplicate_invoice' => 'Šiam laikotarpiui sąskaita jau egzistuoja.',
        'tenant_no_property' => 'Nuomininkas neturi susieto turto.',
        'property_no_meters' => 'Turtas neturi sukonfigūruotų skaitiklių.',
        'provider_not_found' => 'Šiai paslaugai tiekėjas nerastas.',
        'negative_consumption' => 'Aptiktas neteisingas neigiamas suvartojimas skaitikliui :meter.',
        'excessive_consumption' => 'Aptiktas neįprastai didelis suvartojimas skaitikliui :meter.',
    ],
    
    'validation' => [
        'tenant_required' => 'Nuomininkas yra privalomas.',
        'tenant_not_found' => 'Nuomininkas nerastas.',
        'tenant_inactive' => 'Nuomininkas neaktyvus arba ištrintas.',
        'period_start_required' => 'Laikotarpio pradžios data yra privaloma.',
        'period_start_future' => 'Laikotarpio pradžios data negali būti ateityje.',
        'period_end_required' => 'Laikotarpio pabaigos data yra privaloma.',
        'period_end_future' => 'Laikotarpio pabaigos data negali būti ateityje.',
        'period_too_long' => 'Atsiskaitymo laikotarpis negali viršyti 3 mėnesių.',
        'duplicate_invoice' => 'Šiam laikotarpiui sąskaita jau egzistuoja.',
    ],
    
    'fields' => [
        'tenant' => 'Nuomininkas',
        'period_start' => 'Laikotarpio pradžia',
        'period_end' => 'Laikotarpio pabaiga',
    ],
    
    'audit' => [
        'invoice_generated' => 'Sąskaita sėkmingai sugeneruota',
        'invoice_finalized' => 'Sąskaita užbaigta',
    ],
];
