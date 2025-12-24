<?php

declare(strict_types=1);

return [
    'admin' => [
        'activity' => [
            'no_users' => 'Nerasta vartotojų',
            'recent_invoices' => 'Naujausios sąskaitos faktūros',
            'recent_portfolio' => 'Naujausios portfelio veiklos',
            'recent_tenants' => 'Naujausi nuomininkai',
            'recent_users' => 'Naujausi vartotojai',
        ],
        'banner' => [
            'expired_body' => 'Jūsų prenumerata pasibaigė. Atnaujinkite, kad galėtumėte toliau naudoti visas funkcijas.',
            'expired_title' => 'Prenumerata pasibaigė',
            'expiring_body' => 'Jūsų prenumerata greitai baigiasi. Atnaujinkite dabar, kad išvengtumėte paslaugų nutraukimo.',
            'expiring_title' => 'Prenumerata baigiasi',
            'no_subscription_body' => 'Neturite aktyvios prenumeratos. Pasirinkite planą, kad pradėtumėte.',
            'no_subscription_title' => 'Nėra aktyvios prenumeratos',
            'renew' => 'Atnaujinti',
            'renew_now' => 'Atnaujinti dabar',
        ],
        'breakdown' => [
            'administrators' => 'Administratoriai',
            'draft_invoices' => 'Sąskaitų faktūrų juodraščiai',
            'finalized_invoices' => 'Užbaigtos sąskaitos faktūros',
            'invoice_title' => 'Sąskaitos faktūros',
            'managers' => 'Vadybininkai',
            'paid_invoices' => 'Apmokėtos sąskaitos faktūros',
            'tenants' => 'Nuomininkai',
            'users_title' => 'Vartotojai',
        ],
        'org_dashboard' => 'Organizacijos skydelis',
        'portfolio_subtitle' => 'Nekilnojamojo turto portfelio valdymas',
        'quick' => [
            'create_user' => 'Sukurti vartotoją',
            'create_user_desc' => 'Pridėti naują vartotoją į sistemą',
            'settings' => 'Nustatymai',
            'settings_desc' => 'Valdyti sistemos nustatymus ir konfigūraciją',
        ],
        'quick_actions' => [
            'create_tenant_desc' => 'Pridėti naują nuomininką į sistemą',
            'create_tenant_title' => 'Sukurti nuomininką',
            'manage_tenants_desc' => 'Peržiūrėti ir valdyti visus nuomininkus',
            'manage_tenants_title' => 'Valdyti nuomininkus',
            'manage_users_desc' => 'Valdyti vartotojus ir jų leidimus',
            'manage_users_title' => 'Valdyti vartotojus',
            'organization_profile_desc' => 'Redaguoti organizacijos profilį ir nustatymus',
            'organization_profile_title' => 'Organizacijos profilis',
            'title' => 'Greiti veiksmai',
        ],
        'stats' => [
            'active_meters' => 'Aktyvūs skaitikliai',
            'active_tenants' => 'Aktyvūs nuomininkai',
            'total_meter_readings' => 'Iš viso skaitiklių rodmenų',
            'total_properties' => 'Iš viso objektų',
            'total_users' => 'Iš viso vartotojų',
            'unpaid_invoices' => 'Neapmokėtos sąskaitos faktūros',
        ],
        'subscription_card' => [
            'approaching_limit' => 'Artėja prie limito',
            'expires' => 'Baigiasi',
            'plan_type' => 'Plano tipas',
            'properties' => 'Objektai',
            'tenants' => 'Nuomininkai',
            'title' => 'Prenumerata',
        ],
        'system_subtitle' => 'Sistemos analitika ir stebėjimas',
        'title' => 'Administratoriaus skydelis',
    ],
    'manager' => [
        'description' => 'Valdyti objektus, skaitiklius ir atsiskaitymo operacijas',
        'empty' => [
            'drafts' => 'Nerasta sąskaitų faktūrų juodraščių',
            'operations' => 'Nėra laukiančių operacijų',
            'recent' => 'Nėra naujausios veiklos',
        ],
        'hints' => [
            'drafts' => 'Sąskaitos faktūros, kurias reikia užbaigti ir išsiųsti',
            'operations' => 'Objektai, kuriems reikia skaitiklių rodmenų ar dėmesio',
            'recent' => 'Naujausios sąskaitos faktūros ir sistemos veikla',
            'shortcuts' => 'Greitas prieigos prie dažnų valdymo užduočių',
        ],
        'pending_section' => 'Laukiantys veiksmai',
        'quick_actions' => [
            'enter_reading_desc' => 'Įrašyti naujus skaitiklių rodmenis objektams',
            'generate_invoice_desc' => 'Sukurti naujas sąskaitas faktūras nuomininkams',
            'view_buildings' => 'Žiūrėti pastatus',
            'view_buildings_desc' => 'Valdyti pastatus ir jų objektus',
            'view_meters' => 'Žiūrėti skaitiklius',
            'view_meters_desc' => 'Stebėti visus komunalinių paslaugų skaitiklius sistemoje',
            'view_reports' => 'Žiūrėti ataskaitas',
            'view_reports_desc' => 'Analitika ir suvartojimo ataskaitos',
        ],
        'sections' => [
            'drafts' => 'Sąskaitų faktūrų juodraščiai',
            'operations' => 'Laukiančios operacijos',
            'recent' => 'Naujausios veiklos',
            'shortcuts' => 'Greiti veiksmai',
        ],
        'stats' => [
            'active_meters' => 'Aktyvūs skaitikliai',
            'active_tenants' => 'Aktyvūs nuomininkai',
            'draft_invoices' => 'Sąskaitų faktūrų juodraščiai',
            'meters_pending' => 'Skaitikliai laukia rodmenų',
            'overdue_invoices' => 'Pradelstos sąskaitos faktūros',
            'total_properties' => 'Iš viso objektų',
        ],
        'title' => 'Vadybininko skydelis',
    ],
    'tenant' => [
        'alerts' => [
            'no_property_body' => 'Jūsų paskyroje nėra priskirto objekto. Kreipkitės į administratorių.',
            'no_property_title' => 'Nepriskirtas objektas',
        ],
        'balance' => [
            'cta' => 'Mokėti dabar',
            'outstanding' => 'Neapmokėtas balansas',
            'title' => 'Sąskaitos balansas',
        ],
        'consumption' => [
            'current' => 'Dabartinis',
            'description' => 'Komunalinių paslaugų suvartojimo analizė',
            'missing_previous' => 'Nėra ankstesnių duomenų',
            'need_more' => 'Reikia daugiau duomenų analizei',
            'previous' => 'Ankstesnis',
            'since_last' => 'Nuo paskutinio rodmens',
            'title' => 'Suvartojimas',
        ],
        'readings' => [
            'units' => [
                '' => '',
            ],
        ],
        'description' => 'Nuomininko portalas ir paskyrų valdymas',
        'property' => [
            'address' => 'Adresas',
            'area' => 'Plotas',
            'building' => 'Pastatas',
            'title' => 'Objekto informacija',
            'type' => 'Tipas',
        ],
        'quick_actions' => [
            'description' => 'Greitas prieigos prie pagrindinių funkcijų',
            'invoices_desc' => 'Peržiūrėti sąskaitų faktūrų istoriją ir mokėjimo būseną',
            'invoices_title' => 'Mano sąskaitos faktūros',
            'meters_desc' => 'Peržiūrėti skaitiklių rodmenis ir suvartojimą',
            'meters_title' => 'Skaitikliai',
            'property_desc' => 'Informacija apie jūsų objektą',
            'property_title' => 'Mano objektas',
            'title' => 'Greiti veiksmai',
        ],
        'readings' => [
            'date' => 'Data',
            'meter_type' => 'Skaitiklio tipas',
            'reading' => 'Rodmuo',
            'serial' => 'Serijinis numeris',
            'serial_short' => 'Ser. Nr.',
            'title' => 'Skaitiklių rodmenys',
            'units' => 'Vienetai',
        ],
        'stats' => [
            'active_meters' => 'Aktyvūs skaitikliai',
            'total_invoices' => 'Iš viso sąskaitų faktūrų',
            'unpaid_invoices' => 'Neapmokėtos sąskaitos faktūros',
        ],
        'title' => 'Nuomininko skydelis',
    ],
    'widgets' => [
        'admin' => [
            'active_tenants' => [
                'description' => 'Aktyvių nuomininkų skaičius sistemoje',
                'label' => 'Aktyvūs nuomininkai',
            ],
            'draft_invoices' => [
                'description' => 'Sąskaitos faktūros juodraščio būsenoje',
                'label' => 'Sąskaitų faktūrų juodraščiai',
            ],
            'pending_readings' => [
                'description' => 'Skaitikliai, laukiantys naujų rodmenų',
                'label' => 'Laukiantys rodmenys',
            ],
            'total_buildings' => [
                'description' => 'Bendras valdomų pastatų skaičius',
                'label' => 'Iš viso pastatų',
            ],
            'total_properties' => [
                'description' => 'Bendras objektų skaičius sistemoje',
                'label' => 'Iš viso objektų',
            ],
            'total_revenue' => [
                'description' => 'Bendros pajamos iš komunalinių paslaugų',
                'label' => 'Bendros pajamos',
            ],
        ],
        'manager' => [
            'draft_invoices' => [
                'description' => 'Neapmokėtos sąskaitos faktūros, reikalaujančios dėmesio',
                'label' => 'Sąskaitų faktūrų juodraščiai',
            ],
            'pending_readings' => [
                'description' => 'Skaitikliai, kuriems reikia surinkti rodmenis',
                'label' => 'Laukiantys rodmenys',
            ],
            'total_buildings' => [
                'description' => 'Pastatai, kuriuos valdote',
                'label' => 'Mano pastatai',
            ],
            'total_properties' => [
                'description' => 'Objektai, kuriuos valdote',
                'label' => 'Mano objektai',
            ],
        ],
        'tenant' => [
            'invoices' => [
                'description' => 'Jūsų komunalinių paslaugų sąskaitos faktūros ir atsiskaitymo istorija',
                'label' => 'Mano sąskaitos faktūros',
            ],
            'property' => [
                'description' => 'Informacija apie jūsų objektą',
                'label' => 'Mano objektas',
            ],
            'unpaid' => [
                'description' => 'Neapmokėtos sąskaitos faktūros, reikalaujančios mokėjimo',
                'label' => 'Neapmokėtos',
            ],
        ],
    ],
    
    // Universal Utility Dashboard Translations
    'utility_analytics' => 'Komunalinių paslaugų analitika',
    'efficiency_trends' => 'Efektyvumo tendencijos',
    'cost_predictions' => 'Išlaidų prognozės',
    'usage_patterns' => 'Naudojimo šablonai',
    'recommendations' => 'Rekomendacijos',
    'real_time_costs' => 'Realaus laiko išlaidos',
    'service_breakdown' => 'Paslaugų paskirstymas',
    'utility_services_overview' => 'Komunalinių paslaugų apžvalga',
    'recent_activity' => 'Naujausios veiklos',
    
    // Stats and Metrics
    'stats' => [
        'total_properties' => 'Iš viso objektų',
        'active_meters' => 'Aktyvūs skaitikliai',
        'monthly_cost' => 'Mėnesio išlaidos',
        'pending_readings' => 'Laukiantys rodmenys',
    ],
    
    // Filters
    'filters' => [
        'last_3_months' => 'Paskutinius 3 mėnesius',
        'last_6_months' => 'Paskutinius 6 mėnesius',
        'last_12_months' => 'Paskutinius 12 mėnesių',
        'current_year' => 'Dabartiniai metai',
        'current_month' => 'Dabartinis mėnuo',
        'last_month' => 'Praėjęs mėnuo',
    ],
    
    // Cost Tracking
    'current_month_cost' => 'Dabartinio mėnesio išlaidos',
    'year_to_date_cost' => 'Metų išlaidos iki šiol',
    'average_monthly_cost' => 'Vidutinės mėnesio išlaidos',
    'from_last_month' => 'nuo praėjusio mėnesio',
    'total_this_year' => 'iš viso šiais metais',
    'last_6_months_average' => 'paskutinių 6 mėnesių vidurkis',
    
    // Real-Time Cost Widget
    'today_projection' => 'Šiandienos prognozė',
    'current' => 'Dabartinis',
    'projected' => 'Prognozuojamas',
    'complete' => 'baigta',
    'monthly_estimate' => 'Mėnesio įvertinimas',
    'month' => 'mėnuo',
    'no_recent_readings' => 'Nėra naujausių rodmenų',
    'last_updated' => 'Paskutinį kartą atnaujinta',
    'never' => 'Niekada',
    
    // Chart Labels
    'consumption_units' => 'Suvartojimas (vienetai)',
    'months' => 'Mėnesiai',
    'meters' => 'skaitikliai',
    
    // Trends and Analysis
    'trend_increasing' => 'Didėja',
    'trend_decreasing' => 'Mažėja',
    'trend_stable' => 'Stabilus',
    'confidence_high' => 'Didelis pasitikėjimas',
    'confidence_medium' => 'Vidutinis pasitikėjimas',
    'confidence_low' => 'Mažas pasitikėjimas',
    'monthly_prediction' => 'Mėnesio prognozė',
    'yearly_prediction' => 'Metų prognozė',
    'peak_usage' => 'Didžiausias naudojimas',
    'weekly_pattern' => 'Savaitės šablonas',
    'monthly_trend' => 'Mėnesio tendencija',
    
    // Empty States
    'no_efficiency_data' => 'Nėra efektyvumo duomenų',
    'no_prediction_data' => 'Nėra prognozės duomenų',
    'no_pattern_data' => 'Nėra naudojimo šablono duomenų',
    'no_recommendations' => 'Šiuo metu nėra rekomendacijų',
    
    // Recommendations
    'missing_readings_title' => 'Trūksta :service rodmenų',
    'missing_readings_desc' => 'Nerasta naujausių rodmenų :property',
    'add_reading' => 'Pridėti rodmenį',
    'high_usage_title' => 'Aptiktas didelis :service naudojimas',
    'high_usage_desc' => 'Naudojimas padidėjo :percentage% :property',
    'investigate_usage' => 'Tirti naudojimą',
    'low_usage_title' => 'Aptiktas mažas :service naudojimas',
    'low_usage_desc' => 'Naudojimas sumažėjo :percentage% :property',
    'verify_readings' => 'Patikrinti rodmenis',
    'efficiency_title' => 'Energijos efektyvumo galimybė',
    'efficiency_desc' => 'Apsvarstykite energijos taupymo priemones :property',
    'consider_efficiency' => 'Apsvarstykite efektyvumo priemones',

    'widgets' => [
        'admin' => [
            'active_tenants' => [
                'description' => 'Aktyvių nuomininkų skaičius sistemoje',
                'label' => 'Aktyvūs nuomininkai',
            ],
            'draft_invoices' => [
                'description' => 'Sąskaitos faktūros juodraščio būsenoje',
                'label' => 'Sąskaitų faktūrų juodraščiai',
            ],
            'pending_readings' => [
                'description' => 'Skaitikliai, laukiantys naujų rodmenų',
                'label' => 'Laukiantys rodmenys',
            ],
            'total_buildings' => [
                'description' => 'Bendras valdomų pastatų skaičius',
                'label' => 'Iš viso pastatų',
            ],
            'total_properties' => [
                'description' => 'Bendras objektų skaičius sistemoje',
                'label' => 'Iš viso objektų',
            ],
            'total_revenue' => [
                'description' => 'Bendros pajamos iš komunalinių paslaugų',
                'label' => 'Bendros pajamos',
            ],
        ],
        'manager' => [
            'draft_invoices' => [
                'description' => 'Neapmokėtos sąskaitos faktūros, reikalaujančios dėmesio',
                'label' => 'Sąskaitų faktūrų juodraščiai',
            ],
            'pending_readings' => [
                'description' => 'Skaitikliai, kuriems reikia surinkti rodmenis',
                'label' => 'Laukiantys rodmenys',
            ],
            'total_buildings' => [
                'description' => 'Pastatai, kuriuos valdote',
                'label' => 'Mano pastatai',
            ],
            'total_properties' => [
                'description' => 'Objektai, kuriuos valdote',
                'label' => 'Mano objektai',
            ],
        ],
        'tenant' => [
            'invoices' => [
                'description' => 'Jūsų komunalinių paslaugų sąskaitos faktūros ir atsiskaitymo istorija',
                'label' => 'Mano sąskaitos faktūros',
            ],
            'property' => [
                'description' => 'Informacija apie jūsų objektą',
                'label' => 'Mano objektas',
            ],
            'unpaid' => [
                'description' => 'Neapmokėtos sąskaitos faktūros, reikalaujančios mokėjimo',
                'label' => 'Neapmokėtos',
            ],
        ],
    ],

    // Audit System Translations
    'audit' => [
        // Widget Headings
        'overview' => 'Audito apžvalga',
        'trends' => 'Audito tendencijos',
        'trends_title' => 'Audito tendencijos',
        'trends_description' => 'Sekti konfigūracijos pakeitimus ir sistemos veiklą laikui bėgant',
        'compliance_status' => 'Atitikties būsena',
        'anomaly_detection' => 'Anomalijų aptikimas',
        'change_history' => 'Pakeitimų istorija',
        'rollback_management' => 'Atkūrimo valdymas',
        'rollback_history' => 'Atkūrimo istorija',
        
        // Stats and Metrics
        'total_changes' => 'Iš viso pakeitimų',
        'user_changes' => 'Vartotojų pakeitimai',
        'system_changes' => 'Sistemos pakeitimai',
        'compliance_score' => 'Atitikties balas',
        'anomalies_detected' => 'Aptiktos anomalijos',
        'performance_score' => 'Našumo balas',
        'performance_grade' => 'Našumo įvertinimas',
        'system_performance' => 'Sistemos našumas',
        'critical_issues' => 'Kritinės problemos',
        'requires_attention' => 'Reikia dėmesio',
        'last_24_hours' => 'Paskutinės 24 valandos',
        'last_7_days' => 'Paskutinės 7 dienos',
        'last_30_days' => 'Paskutinės 30 dienų',
        'date' => 'Data',
        'number_of_changes' => 'Pakeitimų skaičius',
        
        // Status Messages
        'view_details' => 'Žiūrėti detales',
        'no_anomalies' => 'Anomalijų neaptikta',
        'no_data_available' => 'Nėra audito duomenų',
        'no_rollbacks' => 'Atkūrimų nerasta',
        'no_rollbacks_description' => 'Konfigūracijos atkūrimai dar nebuvo atlikti.',
        'excellent_compliance' => 'Puikus atitikimas',
        'good_compliance' => 'Geras atitikimas',
        'needs_attention' => 'Reikia dėmesio',
        'critical_issues' => 'Kritinės problemos',
        'fully_compliant' => 'Visiškai atitinka',
        'non_compliant' => 'Neatitinka',
        'unknown_status' => 'Nežinoma būsena',
        
        // Modal Titles
        'change_details' => 'Pakeitimo detalės',
        'rollback_details' => 'Atkūrimo detalės',
        'rollback_confirmation' => 'Patvirtinti atkūrimą',
        'rollback_warning' => 'Šis veiksmas grąžins konfigūraciją į ankstesnę būseną. Tai negalima atšaukti.',
        'bulk_rollback_confirmation' => 'Patvirtinti masinio atkūrimo',
        'bulk_rollback_warning' => 'Tai atkurs kelias konfigūracijas. Įsitikinkite, kad tai yra numatyta.',
        'revert_rollback_confirmation' => 'Patvirtinti atkūrimo atšaukimą',
        'revert_rollback_warning' => 'Tai atšauks atkūrimo operaciją. Naudokite atsargiai.',
        
        // Labels
        'labels' => [
            'severity' => 'Sunkumas',
            'details' => 'Detalės',
            'average' => 'Vidurkis',
            'peak' => 'Pikas',
            'threshold' => 'Slenkstis',
            'anomalous' => 'Anomalus',
            'yes' => 'Taip',
            'no' => 'Ne',
            'recommended_actions' => 'Rekomenduojami veiksmai',
            'changed_at' => 'Pakeista',
            'model_type' => 'Modelio tipas',
            'event' => 'Įvykis',
            'user' => 'Vartotojas',
            'system' => 'Sistema',
            'unknown_user' => 'Nežinomas vartotojas',
            'changed_fields' => 'Pakeisti laukai',
            'notes' => 'Pastabos',
            'period' => 'Laikotarpis',
            'performed_at' => 'Atlikta',
            'performed_by' => 'Atliko',
            'configuration' => 'Konfigūracija',
            'reason' => 'Priežastis',
            'fields_rolled_back' => 'Atkurti laukai',
            'original_change' => 'Pradinis pakeitimas',
            'not_available' => 'Nepasiekiama',
            'rollback_reason' => 'Atkūrimo priežastis',
            'revert_reason' => 'Atšaukimo priežastis',
            'select_changes' => 'Pasirinkti pakeitimus',
        ],
        
        // Events
        'events' => [
            'created' => 'Sukurta',
            'updated' => 'Atnaujinta',
            'deleted' => 'Ištrinta',
            'rollback' => 'Atkūrimas',
        ],
        
        // Models
        'models' => [
            'utility_service' => 'Komunalinė paslauga',
            'service_configuration' => 'Paslaugos konfigūracija',
        ],
        
        // Time Periods
        'periods' => [
            'today' => 'Šiandien',
            'this_week' => 'Šią savaitę',
            'this_month' => 'Šį mėnesį',
            'this_quarter' => 'Šį ketvirtį',
            'this_year' => 'Šiais metais',
        ],
        
        // Actions
        'actions' => [
            'export_details' => 'Eksportuoti detales',
            'mark_reviewed' => 'Pažymėti kaip peržiūrėta',
            'refresh' => 'Atnaujinti',
            'view_details' => 'Žiūrėti detales',
            'rollback' => 'Atkūrimas',
            'view_rollback_history' => 'Žiūrėti atkūrimo istoriją',
            'bulk_rollback' => 'Masinis atkūrimas',
            'revert_rollback' => 'Atšaukti atkūrimą',
        ],
        
        // Placeholders
        'placeholders' => [
            'rollback_reason' => 'Paaiškinkite, kodėl ši konfigūracija turėtų būti atkurta...',
            'bulk_rollback_reason' => 'Paaiškinkite, kodėl šios konfigūracijos turėtų būti atkurtos...',
            'revert_reason' => 'Paaiškinkite, kodėl šis atkūrimas turėtų būti atšauktas...',
        ],
        
        // Notifications
        'notifications' => [
            'rollback_success' => 'Konfigūracija sėkmingai atkurta',
            'rollback_failed' => 'Atkūrimo operacija nepavyko',
            'bulk_rollback_success' => 'Sėkmingai atkurtos :count konfigūracijos',
            'bulk_rollback_partial' => 'Atkurtos :success konfigūracijos, :failed nepavyko',
            'revert_success' => 'Atkūrimas sėkmingai atšauktas',
            'revert_failed' => 'Nepavyko atšaukti atkūrimo',
            'original_change_not_found' => 'Pradinio pakeitimo įrašas nerastas',
            'rollback_audit_not_found' => 'Atkūrimo audito įrašas nerastas',
            'anomaly_detected' => [
                'subject' => ':Severity audito anomalija: :type',
                'greeting' => 'Audito įspėjimas',
                'title' => 'Aptikta audito anomalija',
                'intro' => 'Aptikta :severity sunkumo :type anomalija nuomininkui :tenant_id.',
                'detected_at' => 'Aptikta: :time',
                'details_header' => 'Anomalijos detalės:',
                'action' => 'Žiūrėti audito skydelį',
            ],
            'compliance_issue' => [
                'subject' => 'Atitikties balo įspėjimas: :score%',
                'greeting' => 'Atitikties įspėjimas',
                'title' => 'Aptikta atitikties problema',
                'intro' => 'Nuomininko :tenant_id atitikties balas nukrito iki :score%.',
                'summary' => 'Atitikties balas: :score%',
                'failing_categories' => 'Neatitinkančios kategorijos:',
                'recommendations' => 'Rekomendacijos:',
                'action' => 'Žiūrėti atitikties skydelį',
            ],
        ],
        
        // Anomaly Types
        'anomaly_types' => [
            'high_change_frequency' => 'Didelis pakeitimų dažnis',
            'bulk_changes' => 'Masiniai pakeitimai',
            'configuration_rollbacks' => 'Konfigūracijos atkūrimai',
            'unauthorized_access' => 'Neleistina prieiga',
            'data_integrity_issue' => 'Duomenų vientisumo problema',
            'performance_degradation' => 'Našumo pablogėjimas',
        ],
        
        // Compliance Categories
        'compliance_categories' => [
            'audit_trail' => 'Audito pėdsakas',
            'data_retention' => 'Duomenų saugojimas',
            'regulatory' => 'Reguliavimo atitikimas',
            'security' => 'Saugumo atitikimas',
            'data_quality' => 'Duomenų kokybė',
        ],
        
        // Recommendations
        'recommendations' => [
            'investigate_changes' => 'Tirti naujausius pakeitimus',
            'investigate_changes_desc' => 'Peržiūrėkite naujausius konfigūracijos pakeitimus, kad nustatytumėte šablonus ar neleistinus modifikacijas.',
            'review_permissions' => 'Peržiūrėti vartotojų leidimus',
            'review_permissions_desc' => 'Įsitikinkite, kad vartotojai turi tinkamus prieigos lygius ir apsvarstykite papildomų patvirtinimo darbo eigų įdiegimą.',
            'verify_user_actions' => 'Patikrinti vartotojų veiksmus',
            'verify_user_actions_desc' => 'Susisiekite su vartotoju, kad patvirtintumėte, jog šie masiniai pakeitimai buvo numatyti ir autorizuoti.',
            'analyze_rollbacks' => 'Analizuoti atkūrimo šablonus',
            'analyze_rollbacks_desc' => 'Ištirti, kodėl konfigūracijos yra atkuriamos, kad nustatytumėte pagrindines problemas.',
            'review_logs' => 'Peržiūrėti sistemos žurnalus',
            'review_logs_desc' => 'Išnagrinėkite išsamius sistemos žurnalus, kad suprastumėte šios anomalijos kontekstą.',
        ],
    ],
];
