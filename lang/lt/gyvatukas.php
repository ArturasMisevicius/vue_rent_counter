<?php

declare(strict_types=1);

return [
    'attributes' => [
        'billing_month' => 'Atsiskaitymo mėnuo',
        'building' => 'Pastatas',
        'distribution_method' => 'Paskirstymo metodas',
    ],
    'validation' => [
        'billing_month_future' => 'Atsiskaitymo mėnuo ateitis',
        'billing_month_invalid' => 'Neteisingas atsiskaitymo mėnuo',
        'billing_month_required' => 'Reikalingas atsiskaitymo mėnuo',
        'billing_month_too_old' => 'Atsiskaitymo mėnuo per senas',
        'building_not_found' => 'Pastatas Nerastas',
        'building_required' => 'Reikalingas pastatas',
        'distribution_method_invalid' => 'Netinkamas platinimo metodas',
        'no_properties' => 'Nėra savybių',
        'unauthorized_building' => 'Neleistinas pastatas',
    ],
];
