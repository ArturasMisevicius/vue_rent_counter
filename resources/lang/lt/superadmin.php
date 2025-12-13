<?php

declare(strict_types=1);

return [
    'cluster' => [
        'label' => 'Super Administratorius',
        'navigation_label' => 'Super Administratorius',
    ],

    'tenants' => [
        'label' => 'Nuomininkas',
        'plural_label' => 'Nuomininkai',
        'navigation_label' => 'Nuomininkai',
        'navigation_group' => 'Nuomininkų Valdymas',

        'pages' => [
            'list' => [
                'title' => 'Nuomininkai',
            ],
            'create' => [
                'title' => 'Sukurti Nuomininką',
            ],
            'view' => [
                'title' => 'Nuomininkas: :name',
            ],
            'edit' => [
                'title' => 'Redaguoti Nuomininką: :name',
            ],
        ],

        'fields' => [
            'name' => 'Pavadinimas',
            'slug' => 'Nuoroda',
            'domain' => 'Domenas',
            'primary_contact_email' => 'Pagrindinis Kontaktinis El. paštas',
            'status' => 'Būsena',
            'subscription_plan' => 'Prenumeratos Planas',
            'max_users' => 'Maks. Vartotojų',
            'max_storage_gb' => 'Maks. Saugykla (GB)',
            'max_api_calls_per_month' => 'Maks. API Iškvietimų/Mėn.',
            'current_users' => 'Dabartiniai Vartotojai',
            'current_storage_gb' => 'Dabartinė Saugykla (GB)',
            'current_api_calls' => 'Dabartiniai API Iškvietimai',
            'billing_email' => 'Atsiskaitymo El. paštas',
            'billing_name' => 'Atsiskaitymo Vardas',
            'billing_address' => 'Atsiskaitymo Adresas',
            'monthly_price' => 'Mėnesio Kaina',
            'setup_fee' => 'Įdiegimo Mokestis',
            'billing_cycle' => 'Atsiskaitymo Ciklas',
            'next_billing_date' => 'Kita Atsiskaitymo Data',
            'auto_billing' => 'Automatinis Atsiskaitymas',
            'trial_ends_at' => 'Bandomasis Laikotarpis Baigiasi',
            'subscription_ends_at' => 'Prenumerata Baigiasi',
            'enforce_quotas' => 'Vykdyti Kvotas',
            'quota_notifications' => 'Kvotų Pranešimai',
            'timezone' => 'Laiko Juosta',
            'locale' => 'Kalba',
            'currency' => 'Valiuta',
            'allow_registration' => 'Leisti Registraciją',
            'require_email_verification' => 'Reikalauti El. pašto Patvirtinimo',
            'maintenance_mode' => 'Priežiūros Režimas',
            'api_access_enabled' => 'API Prieiga Įjungta',
            'suspended_at' => 'Sustabdyta',
            'suspension_reason' => 'Sustabdymo Priežastis',
            'created_at' => 'Sukurta',
            'updated_at' => 'Atnaujinta',
        ],

        'sections' => [
            'basic_info' => 'Pagrindinė Informacija',
            'subscription' => 'Prenumerata ir Limitai',
            'billing' => 'Atsiskaitymo Konfigūracija',
            'quotas' => 'Išteklių Kvotės',
            'settings' => 'Nuomininko Nustatymai',
            'suspension' => 'Sustabdymo Informacija',
            'status_management' => 'Būsenos Valdymas',
            'metadata' => 'Metaduomenys',
            'metrics' => 'Metrikos',
        ],

        'billing_cycles' => [
            'monthly' => 'Kas mėnesį',
            'quarterly' => 'Kas ketvirtį',
            'yearly' => 'Kas metus',
        ],

        'help' => [
            'current_users' => 'Šiuo metu registruotų vartotojų skaičius',
            'current_storage' => 'Šiuo metu naudojama saugykla',
            'current_api_calls' => 'Šį mėnesį atlikti API iškvietimai',
            'enforce_quotas' => 'Neleisti viršyti išteklių limitų',
            'quota_notifications' => 'Siųsti pranešimus artėjant prie limitų',
            'allow_registration' => 'Leisti naujiems vartotojams registruotis',
            'require_email_verification' => 'Reikalauti el. pašto patvirtinimo naujiems vartotojams',
            'maintenance_mode' => 'Įjungti nuomininko priežiūros režimą',
            'api_access_enabled' => 'Įjungti API prieigą šiam nuomininkui',
        ],

        'actions' => [
            'view' => 'Peržiūrėti',
            'edit' => 'Redaguoti',
            'delete' => 'Ištrinti',
            'suspend' => 'Sustabdyti',
            'activate' => 'Aktyvuoti',
            'impersonate' => 'Apsimesti',
            'view_users' => 'Peržiūrėti Vartotojus',
            'export' => 'Eksportuoti',
            'bulk_suspend' => 'Sustabdyti Pasirinktus',
            'bulk_activate' => 'Aktyvuoti Pasirinktus',
            'bulk_delete' => 'Ištrinti Pasirinktus',
        ],

        'filters' => [
            'status' => 'Būsena',
            'subscription_plan' => 'Prenumeratos Planas',
            'created_date' => 'Sukūrimo Data',
        ],

        'modals' => [
            'suspend' => [
                'heading' => 'Sustabdyti Nuomininką',
                'description' => 'Ar tikrai norite sustabdyti šį nuomininką? Vartotojai negalės prisijungti prie savo paskyros.',
                'reason_label' => 'Sustabdymo Priežastis',
                'reason_placeholder' => 'Įveskite sustabdymo priežastį...',
                'confirm' => 'Sustabdyti Nuomininką',
            ],
            'activate' => [
                'heading' => 'Aktyvuoti Nuomininką',
                'description' => 'Ar tikrai norite aktyvuoti šį nuomininką?',
                'confirm' => 'Aktyvuoti Nuomininką',
            ],
            'delete' => [
                'heading' => 'Ištrinti Nuomininką',
                'description' => 'Ar tikrai norite ištrinti šį nuomininką? Šis veiksmas negrįžtamas.',
                'confirm' => 'Ištrinti Nuomininką',
            ],
            'bulk_suspend' => [
                'heading' => 'Sustabdyti Pasirinktus Nuomininkus',
                'description' => 'Ar tikrai norite sustabdyti pasirinktus nuomininkus?',
                'reason_label' => 'Sustabdymo Priežastis',
                'confirm' => 'Sustabdyti Nuomininkus',
            ],
        ],

        'notifications' => [
            'suspended' => 'Nuomininkas sėkmingai sustabdytas',
            'activated' => 'Nuomininkas sėkmingai aktyvuotas',
            'deleted' => 'Nuomininkas sėkmingai ištrintas',
            'bulk_suspended' => 'Pasirinkti nuomininkai sėkmingai sustabdyti',
            'bulk_activated' => 'Pasirinkti nuomininkai sėkmingai aktyvuoti',
            'bulk_deleted' => 'Pasirinkti nuomininkai sėkmingai ištrinti',
        ],

        'empty_state' => [
            'heading' => 'Nuomininkų nerasta',
            'description' => 'Pradėkite sukurdami pirmą nuomininką.',
        ],
    ],

    'users' => [
        'label' => 'Sistemos Vartotojas',
        'plural_label' => 'Sistemos Vartotojai',
        'navigation_label' => 'Sistemos Vartotojai',
        'navigation_group' => 'Vartotojų Valdymas',

        'pages' => [
            'list' => [
                'title' => 'Sistemos Vartotojai',
            ],
            'create' => [
                'title' => 'Sukurti Sistemos Vartotoją',
            ],
            'view' => [
                'title' => 'Vartotojas: :name',
            ],
            'edit' => [
                'title' => 'Redaguoti Vartotoją: :name',
            ],
            'activity_report' => [
                'title' => 'Veiklos Ataskaita: :name',
            ],
        ],

        'fields' => [
            'name' => 'Vardas',
            'email' => 'El. paštas',
            'email_verified_at' => 'El. paštas Patvirtintas',
            'is_super_admin' => 'Super Administratorius',
            'is_suspended' => 'Sustabdytas',
            'suspension_reason' => 'Sustabdymo Priežastis',
            'suspended_at' => 'Sustabdyta',
            'last_login_at' => 'Paskutinis Prisijungimas',
            'login_count' => 'Prisijungimų Skaičius',
            'current_team_id' => 'Dabartinė Komanda',
            'created_at' => 'Sukurta',
            'updated_at' => 'Atnaujinta',
            'password' => 'Slaptažodis',
            'password_confirmation' => 'Patvirtinti Slaptažodį',
        ],

        'sections' => [
            'basic_info' => 'Pagrindinė Informacija',
            'permissions' => 'Leidimai ir Prieiga',
            'activity' => 'Veikla ir Sesijos',
            'metadata' => 'Metaduomenys',
        ],

        'actions' => [
            'view' => 'Peržiūrėti',
            'edit' => 'Redaguoti',
            'delete' => 'Ištrinti',
            'suspend' => 'Sustabdyti',
            'reactivate' => 'Aktyvuoti',
            'impersonate' => 'Apsimesti',
            'view_activity' => 'Peržiūrėti Veiklą',
            'reset_password' => 'Atstatyti Slaptažodį',
            'send_verification' => 'Siųsti Patvirtinimo Laišką',
            'bulk_suspend' => 'Sustabdyti Pasirinktus',
            'bulk_reactivate' => 'Aktyvuoti Pasirinktus',
            'bulk_delete' => 'Ištrinti Pasirinktus',
        ],

        'filters' => [
            'is_super_admin' => 'Super Administratorius',
            'is_suspended' => 'Sustabdytas',
            'email_verified' => 'El. paštas Patvirtintas',
            'last_login' => 'Paskutinis Prisijungimas',
        ],

        'modals' => [
            'suspend' => [
                'heading' => 'Sustabdyti Vartotoją',
                'description' => 'Ar tikrai norite sustabdyti šį vartotoją?',
                'reason_label' => 'Sustabdymo Priežastis',
                'confirm' => 'Sustabdyti Vartotoją',
            ],
            'reactivate' => [
                'heading' => 'Aktyvuoti Vartotoją',
                'description' => 'Ar tikrai norite aktyvuoti šį vartotoją?',
                'confirm' => 'Aktyvuoti Vartotoją',
            ],
            'delete' => [
                'heading' => 'Ištrinti Vartotoją',
                'description' => 'Ar tikrai norite ištrinti šį vartotoją? Šis veiksmas negrįžtamas.',
                'confirm' => 'Ištrinti Vartotoją',
            ],
            'impersonate' => [
                'heading' => 'Apsimesti Vartotoju',
                'description' => 'Jūs ketinate apsimesti šiuo vartotoju. Visi veiksmai bus registruojami.',
                'confirm' => 'Pradėti Apsimetimą',
            ],
        ],

        'notifications' => [
            'suspended' => 'Vartotojas sėkmingai sustabdytas',
            'reactivated' => 'Vartotojas sėkmingai aktyvuotas',
            'deleted' => 'Vartotojas sėkmingai ištrintas',
            'password_reset' => 'Slaptažodžio atstatymo laiškas išsiųstas',
            'verification_sent' => 'Patvirtinimo laiškas išsiųstas',
            'impersonation_started' => 'Apsimetimas pradėtas',
        ],

        'activity' => [
            'total_logins' => 'Iš Viso Prisijungimų',
            'last_login' => 'Paskutinis Prisijungimas',
            'account_age' => 'Paskyros Amžius',
            'teams_count' => 'Komandos',
            'recent_activity' => 'Paskutinė Veikla',
            'no_activity' => 'Paskutinės veiklos nerasta',
        ],
    ],

    'audit' => [
        'label' => 'Audito Žurnalas',
        'plural_label' => 'Audito Žurnalai',
        'navigation_label' => 'Audito Žurnalai',
        'navigation_group' => 'Sistemos Stebėjimas',

        'pages' => [
            'list' => [
                'title' => 'Audito Žurnalai',
            ],
            'view' => [
                'title' => 'Audito Žurnalo Įrašas',
            ],
        ],

        'fields' => [
            'admin_id' => 'Administratorius',
            'action' => 'Veiksmas',
            'target_type' => 'Objekto Tipas',
            'target_id' => 'Objekto ID',
            'changes' => 'Pakeitimai',
            'ip_address' => 'IP Adresas',
            'user_agent' => 'Naršyklė',
            'created_at' => 'Sukurta',
        ],

        'sections' => [
            'basic_info' => 'Pagrindinė Informacija',
            'changes' => 'Atlikti Pakeitimai',
            'metadata' => 'Užklausos Metaduomenys',
        ],

        'actions' => [
            'view' => 'Peržiūrėti Detales',
            'export' => 'Eksportuoti',
        ],

        'filters' => [
            'action' => 'Veiksmas',
            'admin' => 'Administratorius',
            'target_type' => 'Objekto Tipas',
            'date_range' => 'Datų Intervalas',
            'ip_address' => 'IP Adresas',
        ],

        'action' => [
            'tenant_created' => 'Nuomininkas Sukurtas',
            'tenant_updated' => 'Nuomininkas Atnaujintas',
            'tenant_suspended' => 'Nuomininkas Sustabdytas',
            'tenant_activated' => 'Nuomininkas Aktyvuotas',
            'tenant_deleted' => 'Nuomininkas Ištrintas',
            'user_impersonated' => 'Vartotojas Apsimestas',
            'impersonation_ended' => 'Apsimetimas Baigtas',
            'bulk_operation' => 'Masinis Veiksmas',
            'system_config_changed' => 'Sistemos Konfigūracija Pakeista',
            'system_config_created' => 'Sistemos Konfigūracija Sukurta',
            'system_config_updated' => 'Sistemos Konfigūracija Atnaujinta',
            'system_config_deleted' => 'Sistemos Konfigūracija Ištrinta',
            'backup_created' => 'Atsarginė Kopija Sukurta',
            'backup_restored' => 'Atsarginė Kopija Atkurta',
            'notification_sent' => 'Pranešimas Išsiųstas',
            'resource_quota_changed' => 'Išteklių Kvota Pakeista',
            'billing_updated' => 'Atsiskaitymas Atnaujintas',
            'feature_flag_changed' => 'Funkcijos Vėliavėlė Pakeista',
            'user_suspended' => 'Vartotojas Sustabdytas',
            'user_reactivated' => 'Vartotojas Aktyvuotas',
        ],

        'empty_state' => [
            'heading' => 'Audito žurnalų nerasta',
            'description' => 'Audito žurnalai atsiras čia atliekant veiksmus.',
        ],

        'changes' => [
            'no_changes' => 'Pakeitimų neužregistruota',
            'from' => 'Iš',
            'to' => 'Į',
        ],
    ],

    'config' => [
        'label' => 'Sistemos Konfigūracija',
        'plural_label' => 'Sistemos Konfigūracijos',
        'navigation_label' => 'Sistemos Konfigūracija',
        'navigation_group' => 'Sistemos Valdymas',

        'pages' => [
            'list' => [
                'title' => 'Sistemos Konfigūracija',
            ],
            'create' => [
                'title' => 'Sukurti Konfigūraciją',
            ],
            'view' => [
                'title' => 'Konfigūracija: :key',
            ],
            'edit' => [
                'title' => 'Redaguoti Konfigūraciją: :key',
            ],
        ],

        'fields' => [
            'key' => 'Raktas',
            'category' => 'Kategorija',
            'type' => 'Tipas',
            'value' => 'Reikšmė',
            'description' => 'Aprašymas',
            'is_sensitive' => 'Slaptas',
            'created_by' => 'Sukūrė',
            'updated_by' => 'Atnaujino',
            'created_at' => 'Sukurta',
            'updated_at' => 'Atnaujinta',
        ],

        'sections' => [
            'basic_info' => 'Pagrindinė Informacija',
            'value' => 'Reikšmės Konfigūracija',
            'metadata' => 'Metaduomenys',
        ],

        'actions' => [
            'view' => 'Peržiūrėti',
            'edit' => 'Redaguoti',
            'delete' => 'Ištrinti',
            'export' => 'Eksportuoti',
        ],

        'filters' => [
            'category' => 'Kategorija',
            'type' => 'Tipas',
            'is_sensitive' => 'Slaptas',
        ],

        'modals' => [
            'delete' => [
                'heading' => 'Ištrinti Konfigūraciją',
                'description' => 'Ar tikrai norite ištrinti šią konfigūraciją? Šis veiksmas negrįžtamas.',
                'confirm' => 'Ištrinti Konfigūraciją',
            ],
        ],

        'notifications' => [
            'created' => 'Konfigūracija sėkmingai sukurta',
            'created_body' => 'Konfigūracija ":key" buvo sukurta.',
            'updated' => 'Konfigūracija sėkmingai atnaujinta',
            'updated_body' => 'Konfigūracija ":key" buvo atnaujinta.',
            'deleted' => 'Konfigūracija sėkmingai ištrinta',
        ],

        'types' => [
            'string' => 'Tekstas',
            'integer' => 'Sveikasis Skaičius',
            'float' => 'Dešimtainis Skaičius',
            'boolean' => 'Loginis',
            'array' => 'Masyvas',
            'json' => 'JSON',
        ],

        'categories' => [
            'general' => 'Bendri',
            'security' => 'Saugumas',
            'billing' => 'Atsiskaitymas',
            'features' => 'Funkcijos',
            'integrations' => 'Integracijos',
            'notifications' => 'Pranešimai',
        ],

        'empty_state' => [
            'heading' => 'Konfigūracijų nerasta',
            'description' => 'Pradėkite sukurdami pirmą sistemos konfigūraciją.',
        ],
    ],

    'dashboard' => [
        'title' => 'Super Administratoriaus Skydelis',
        'widgets' => [
            'tenant_overview' => [
                'title' => 'Nuomininkų Apžvalga',
                'total_tenants' => 'Iš Viso Nuomininkų',
                'active_tenants' => 'Aktyvūs Nuomininkai',
                'suspended_tenants' => 'Sustabdyti Nuomininkai',
                'trial_tenants' => 'Bandomieji Nuomininkai',
            ],
            'system_metrics' => [
                'title' => 'Sistemos Metrikos',
                'total_users' => 'Iš Viso Vartotojų',
                'active_sessions' => 'Aktyvios Sesijos',
                'api_calls_today' => 'API Iškvietimai Šiandien',
                'storage_used' => 'Panaudota Saugykla',
            ],
            'recent_activity' => [
                'title' => 'Paskutinė Veikla',
                'no_activity' => 'Paskutinės veiklos nėra',
            ],
        ],
    ],

    'common' => [
        'actions' => [
            'save' => 'Išsaugoti',
            'cancel' => 'Atšaukti',
            'delete' => 'Ištrinti',
            'edit' => 'Redaguoti',
            'view' => 'Peržiūrėti',
            'create' => 'Sukurti',
            'update' => 'Atnaujinti',
            'search' => 'Ieškoti',
            'filter' => 'Filtruoti',
            'export' => 'Eksportuoti',
            'import' => 'Importuoti',
            'refresh' => 'Atnaujinti',
        ],
        'status' => [
            'active' => 'Aktyvus',
            'inactive' => 'Neaktyvus',
            'suspended' => 'Sustabdytas',
            'pending' => 'Laukiantis',
            'trial' => 'Bandomasis',
        ],
        'messages' => [
            'no_results' => 'Rezultatų nerasta',
            'loading' => 'Kraunama...',
            'success' => 'Operacija sėkmingai atlikta',
            'error' => 'Įvyko klaida',
        ],
    ],
];