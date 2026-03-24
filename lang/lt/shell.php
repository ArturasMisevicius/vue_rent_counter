<?php

return [
    'search' => [
        'label' => 'Bendroji paieška',
        'placeholder' => 'Ieškoti visko',
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
            'languages' => 'Kalbos',
            'translations' => 'Vertimai',
            'translation_management' => 'Vertimų valdymas',
            'system_configuration' => 'Sistemos konfigūracija',
            'platform_notifications' => 'Platformos pranešimai',
            'audit_logs' => 'Audito žurnalai',
            'security_violations' => 'Saugumo pažeidimai',
            'integration_health' => 'Integracijų būklė',
            'profile' => 'Profilis',
            'reports' => 'Ataskaitos',
            'settings' => 'Nustatymai',
            'organization_users' => 'Organizacijos naudotojai',
            'projects' => 'Projektai',
            'tasks' => 'Užduotys',
            'task_assignments' => 'Užduočių priskyrimai',
            'time_entries' => 'Laiko įrašai',
            'comments' => 'Komentarai',
            'comment_reactions' => 'Komentarų reakcijos',
            'attachments' => 'Priedai',
            'tags' => 'Žymos',
            'property_assignments' => 'Turto priskyrimai',
            'invoice_items' => 'Sąskaitų eilutės',
            'invoice_payments' => 'Sąskaitų mokėjimai',
            'invoice_reminder_logs' => 'Sąskaitų priminimų žurnalai',
            'invoice_email_logs' => 'Sąskaitų el. laiškų žurnalai',
            'subscription_payments' => 'Prenumeratų mokėjimai',
            'subscription_renewals' => 'Prenumeratų atnaujinimai',
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
            'description' => 'Atnaujinkite pilną vardą, el. pašto adresą, telefono numerį ir pageidaujamą kalbą.',
        ],
        'password' => [
            'heading' => 'Slaptažodžio keitimas',
            'description' => 'Keiskite slaptažodį tik tada, kai to tikrai reikia.',
            'note' => 'Palikite visus slaptažodžio laukus tuščius, jei norite išlaikyti dabartinį slaptažodį.',
        ],
        'fields' => [
            'name' => 'Pilnas vardas',
            'email' => 'El. pašto adresas',
            'phone' => 'Telefono numeris',
            'locale' => 'Pageidaujama kalba',
            'current_password' => 'Dabartinis slaptažodis',
            'password' => 'Naujas slaptažodis',
            'password_confirmation' => 'Pakartokite naują slaptažodį',
        ],
        'actions' => [
            'save' => 'Išsaugoti pakeitimus',
            'update_password' => 'Atnaujinti slaptažodį',
        ],
        'messages' => [
            'saved' => 'Jūsų profilis atnaujintas.',
            'password_updated' => 'Jūsų slaptažodis atnaujintas.',
        ],
    ],
    'settings' => [
        'title' => 'Nustatymai',
        'actions' => [
            'save' => 'Išsaugoti pakeitimus',
        ],
        'organization' => [
            'heading' => 'Organizacijos nustatymai',
            'description' => 'Tvarkykite organizacijos informaciją, naudojamą apskaitoje ir paskyros nustatymuose.',
            'fields' => [
                'organization_name' => 'Organizacijos pavadinimas',
                'billing_contact_email' => 'Apskaitos el. pašto adresas',
                'invoice_footer' => 'Numatytosios sąskaitos pastabos apačioje',
            ],
            'help' => [
                'billing_contact_email' => 'Šiuo adresu siunčiami su apskaita susiję pranešimai.',
            ],
        ],
        'notifications' => [
            'heading' => 'Pranešimų nustatymai',
            'description' => 'Pasirinkite, kokius operacinius laiškus administratoriai gaus šiai organizacijai.',
            'fields' => [
                'new_invoice_generated' => 'Pranešti man, kai sugeneruojama nauja sąskaita',
                'invoice_overdue' => 'Pranešti man, kai sąskaita tampa pradelsta',
                'tenant_submits_reading' => 'Pranešti man, kai nuomininkas pateikia skaitiklio rodmenį',
                'subscription_expiring' => 'Pranešti man, kai artėja prenumeratos pabaiga',
            ],
        ],
        'subscription' => [
            'heading' => 'Prenumerata',
            'description' => 'Peržiūrėkite aktyvų planą ir dabartinį panaudojimą prieš atnaujindami ar keisdami planą.',
            'fields' => [
                'current_plan' => 'Dabartinis planas',
                'status' => 'Prenumeratos būsena',
                'expiry_date' => 'Prenumeratos galiojimo pabaiga',
                'plan' => 'Planas',
                'duration' => 'Trukmė',
            ],
            'usage_summary' => ':used iš :limit :label panaudota',
            'limit_reached' => ':label limitas pasiektas.',
            'panel' => [
                'heading' => 'Atnaujinti arba pakeisti planą',
                'description' => 'Pasirinkite naują planą ir trukmę, kad atnaujintumėte limitus.',
            ],
            'actions' => [
                'renew_upgrade' => 'Atnaujinti arba pakeisti planą',
                'cancel' => 'Atšaukti',
                'confirm' => 'Atnaujinti arba pakeisti planą',
            ],
        ],
        'messages' => [
            'organization_saved' => 'Organizacijos nustatymai atnaujinti.',
            'subscription_renewed' => 'Prenumerata atnaujinta.',
        ],
    ],
    'actions' => [
        'back_to_dashboard' => 'Grįžti į skydelį',
        'destructive_confirm_single' => 'Šio veiksmo atšaukti negalima. Jūs ketinate negrįžtamai paveikti :item.',
        'destructive_confirm_bulk' => 'Šio veiksmo atšaukti negalima. Jūs ketinate negrįžtamai paveikti visus pasirinktus įrašus.',
        'destructive_item_fallback' => 'šį įrašą',
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
