<?php

return [
    'search' => [
        'label' => 'Bendroji paieška',
        'placeholder' => 'Search anything',
        'groups' => [
            'platform' => 'Platforma',
            'organization' => 'Organizacija',
            'tenant' => 'Nuomininkas',
        ],
        'empty' => [
            'heading' => 'Rezultatų dar nėra',
            'body' => 'Rezultatai bus rodomi čia, kai atsiras atitinkančių maršrutų ir įrašų.',
        ],
    ],
    'navigation' => [
        'groups' => [
            'platform' => 'Platforma',
            'organization' => 'Organizacija',
            'account' => 'Paskyra',
        ],
        'items' => [
            'organizations' => 'Organizacijos',
            'profile' => 'Profilis',
        ],
    ],
    'roles' => [
        'superadmin' => 'Superadministratorius',
        'admin' => 'Administratorius',
        'manager' => 'Vadybininkas',
        'tenant' => 'Nuomininkas',
    ],
    'profile' => [
        'title' => 'Mano profilis',
        'eyebrow' => 'Paskyros erdvė',
        'heading' => 'Mano profilis',
        'description' => 'Peržiūrėkite savo paskyros tapatybę, pasirinktą kalbą ir prisijungimo kontekstą vienoje bendroje vietoje.',
    ],
    'actions' => [
        'back_to_dashboard' => 'Grįžti į skydelį',
    ],
    'impersonation' => [
        'eyebrow' => 'Aktyvus apsimetimas',
        'heading' => 'Jūs naudojatės šia paskyra apsimesdami kitu naudotoju',
        'actions' => [
            'stop' => 'Nutraukti apsimetimą',
        ],
    ],
    'errors' => [
        'eyebrow' => 'Klaida :status',
        '403' => [
            'title' => 'Jūs neturite teisės peržiūrėti šio puslapio',
            'description' => 'Jūsų paskyra šiuo metu neturi prieigos prie šios srities. Jei manote, kad tai klaida, susisiekite su administratoriumi arba grįžkite į tinkamą skydelį.',
        ],
        '404' => [
            'title' => 'Jūsų ieškomas puslapis neegzistuoja',
            'description' => 'Nuoroda gali būti pasenusi, nepilna arba nebepasiekiama. Grįžkite į savo skydelį ir tęskite darbą saugiai.',
        ],
        '500' => [
            'title' => 'Mūsų pusėje kažkas nutiko',
            'description' => 'Šiuo metu negalėjome įvykdyti šios užklausos. Pabandykite dar kartą po akimirkos arba susisiekite su pagalba, jei problema kartosis.',
        ],
    ],
    'notifications' => [
        'heading' => 'Pranešimai',
        'unread_count' => '{0} Nėra neskaitytų pranešimų|{1} :count neskaitytas pranešimas|[2,*] :count neskaityti pranešimai',
        'actions' => [
            'toggle' => 'Perjungti pranešimus',
            'mark_all_read' => 'Pažymėti visus kaip skaitytus',
        ],
        'empty' => [
            'heading' => 'Pranešimų dar nėra',
            'body' => 'Nauji atnaujinimai čia atsiras, kai sistema turės ką parodyti.',
        ],
        'defaults' => [
            'title' => 'Pranešimas',
            'body' => 'Yra naujų pranešimo detalių.',
            'just_now' => 'ką tik',
        ],
    ],
];
