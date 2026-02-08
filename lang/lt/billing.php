<?php

declare(strict_types=1);

return [
    'fields' => [
        'period_end' => 'Laikotarpio pabaiga',
        'period_start' => 'Laikotarpio pradžia',
        'tenant' => 'Nuomininkas',
    ],
    'validation' => [
        'duplicate_invoice' => 'Sąskaitos faktūros dublikatas',
        'period_end_future' => 'Laikotarpio pabaiga Ateitis',
        'period_end_required' => 'Reikalingas laikotarpio pabaiga',
        'period_start_future' => 'Laikotarpio pradžia Ateitis',
        'period_start_required' => 'Reikalingas laikotarpio pradžia',
        'period_too_long' => 'Per ilgas laikotarpis',
        'tenant_inactive' => 'Nuomininkas neaktyvus',
        'tenant_not_found' => 'Nuomininkas Nerastas',
        'tenant_required' => 'Reikalingas nuomininkas',
    ],
];
