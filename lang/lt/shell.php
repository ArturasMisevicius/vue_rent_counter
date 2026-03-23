<?php

return [
    'search' => [
        'label' => 'Bendroji paieška',
        'placeholder' => 'Search anything',
        'groups' => [
            'organizations' => 'Organizacijos',
            'buildings' => 'Pastatai',
            'properties' => 'Objektai',
            'tenants' => 'Nuomininkai',
            'invoices' => 'Sąskaitos',
            'readings' => 'Rodmenys',
        ],
        'empty' => [
            'heading' => 'Rezultatų dar nėra',
            'body' => 'Rezultatai bus rodomi čia, kai dabartinėje darbo srityje atsiras atitinkančių įrašų.',
        ],
    ],
    'navigation' => [
        'groups' => [
            'platform' => 'Platforma',
            'properties' => 'Turtas',
            'billing' => 'Apskaita',
            'reports' => 'Ataskaitos',
            'my_home' => 'Mano namai',
            'organization' => 'Organizacija',
            'account' => 'Paskyra',
        ],
        'items' => [
            'organizations' => 'Organizacijos',
            'users' => 'Naudotojai',
            'subscriptions' => 'Prenumeratos',
            'platform_notifications' => 'Platformos pranešimai',
            'languages' => 'Kalbos',
            'translation_management' => 'Vertimų valdymas',
            'system_configuration' => 'Sistemos konfigūracija',
            'audit_logs' => 'Audito žurnalai',
            'security_violations' => 'Saugumo pažeidimai',
            'integration_health' => 'Integracijų būklė',
            'profile' => 'Profilis',
            'reports' => 'Ataskaitos',
            'settings' => 'Nustatymai',
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
        'personal_information' => [
            'heading' => 'Asmeninė informacija',
            'description' => 'Atnaujinkite rodomą vardą, el. pašto adresą ir pageidaujamą kalbą.',
        ],
        'password' => [
            'heading' => 'Slaptažodžio keitimas',
            'description' => 'Nustatykite naują paskyros slaptažodį ir patvirtinkite jį prieš išsaugodami.',
        ],
        'fields' => [
            'name' => 'Vardas',
            'email' => 'El. paštas',
            'locale' => 'Kalba',
            'current_password' => 'Dabartinis slaptažodis',
            'password' => 'Naujas slaptažodis',
            'password_confirmation' => 'Pakartokite naują slaptažodį',
        ],
        'actions' => [
            'save' => 'Išsaugoti profilį',
            'update_password' => 'Atnaujinti slaptažodį',
        ],
        'messages' => [
            'saved' => 'Jūsų profilis atnaujintas.',
            'password_updated' => 'Jūsų slaptažodis atnaujintas.',
        ],
    ],
    'settings' => [
        'title' => 'Nustatymai',
        'organization' => [
            'heading' => 'Organizacijos nustatymai',
            'description' => 'Tvarkykite atsiskaitymų kontaktus ir mokėjimo informaciją, kuri bus rodoma būsimiems naudotojams.',
            'fields' => [
                'billing_contact_name' => 'Atsiskaitymų kontakto vardas',
                'billing_contact_email' => 'Atsiskaitymų kontakto el. paštas',
                'billing_contact_phone' => 'Atsiskaitymų kontakto telefonas',
                'payment_instructions' => 'Mokėjimo instrukcijos',
                'invoice_footer' => 'Sąskaitos poraštė',
            ],
            'actions' => [
                'save' => 'Išsaugoti organizacijos nustatymus',
            ],
        ],
        'notifications' => [
            'heading' => 'Pranešimų nustatymai',
            'description' => 'Pasirinkite, kokius operacinius laiškus administratoriai gaus šiai organizacijai.',
            'fields' => [
                'new_invoice_generated' => 'Sugeneruota nauja sąskaita',
                'invoice_overdue' => 'Pradelsta sąskaita',
                'tenant_submits_reading' => 'Nuomininkas pateikia rodmenį',
                'subscription_expiring' => 'Baigiasi prenumerata',
            ],
            'help' => [
                'new_invoice_generated' => 'Siųsti laišką administratoriams, kai organizacijoje užbaigiama nauja sąskaita.',
                'invoice_overdue' => 'Siųsti laišką administratoriams, kai inicijuojami pradelstų sąskaitų priminimai.',
                'tenant_submits_reading' => 'Siųsti laišką administratoriams, kai nuomininkas pateikia naują skaitiklio rodmenį.',
                'subscription_expiring' => 'Siųsti laišką administratoriams prieš pasibaigiant dabartinei prenumeratai.',
            ],
            'actions' => [
                'save' => 'Išsaugoti pranešimų nustatymus',
            ],
        ],
        'subscription' => [
            'heading' => 'Prenumerata',
            'description' => 'Atnaujinkite dabartinį planą ir atnaujinkite organizacijos limitus.',
            'fields' => [
                'plan' => 'Planas',
                'duration' => 'Trukmė',
            ],
            'plans' => [
                'basic' => 'Basic',
                'professional' => 'Professional',
                'enterprise' => 'Enterprise',
            ],
            'durations' => [
                'monthly' => 'Mėnesinė',
                'quarterly' => 'Ketvirtinė',
                'yearly' => 'Metinė',
            ],
            'actions' => [
                'renew' => 'Atnaujinti prenumeratą',
            ],
        ],
        'messages' => [
            'organization_saved' => 'Organizacijos nustatymai atnaujinti.',
            'notifications_saved' => 'Pranešimų nustatymai atnaujinti.',
            'subscription_renewed' => 'Prenumerata atnaujinta.',
        ],
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
        403 => [
            'title' => 'Jūs neturite teisės peržiūrėti šio puslapio',
            'description' => 'Jūsų paskyra šiuo metu neturi prieigos prie šios srities. Jei manote, kad tai klaida, susisiekite su administratoriumi arba grįžkite į tinkamą skydelį.',
        ],
        404 => [
            'title' => 'Jūsų ieškomas puslapis neegzistuoja',
            'description' => 'Nuoroda gali būti pasenusi, nepilna arba nebepasiekiama. Grįžkite į savo skydelį ir tęskite darbą saugiai.',
        ],
        500 => [
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
        'status' => [
            'read' => 'Skaityta',
            'unread' => 'Neskaityta',
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
