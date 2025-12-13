<?php

return [
    'meter_type' => [
        'electricity' => 'Elektra',
        'water_cold' => 'Šaltas vanduo',
        'water_hot' => 'Karštas vanduo',
        'heating' => 'Šildymas',
    ],

    'property_type' => [
        'apartment' => 'Butas',
        'house' => 'Namas',
    ],

    'service_type' => [
        'electricity' => 'Elektra',
        'water' => 'Vanduo',
        'heating' => 'Šildymas',
    ],

    'invoice_status' => [
        'draft' => 'Juodraštis',
        'finalized' => 'Galutinis',
        'paid' => 'Apmokėtas',
    ],

    'user_role' => [
        'superadmin' => 'Super administratorius',
        'admin' => 'Administratorius',
        'manager' => 'Vadybininkas',
        'tenant' => 'Nuomininkas',
    ],

    'tariff_type' => [
        'flat' => 'Fiksuotas tarifas',
        'time_of_use' => 'Laiko zonų tarifas',
    ],

    'tariff_zone' => [
        'day' => 'Dieninis tarifas',
        'night' => 'Naktinis tarifas',
        'weekend' => 'Savaitgalio tarifas',
    ],

    'weekend_logic' => [
        'apply_night_rate' => 'Taikyti naktinį tarifą savaitgaliais',
        'apply_day_rate' => 'Taikyti dieninį tarifą savaitgaliais',
        'apply_weekend_rate' => 'Taikyti savaitgalio tarifą',
    ],

    'subscription_plan_type' => [
        'basic' => 'Pagrindinis',
        'professional' => 'Profesionalus',
        'enterprise' => 'Įmonės',
    ],

    'subscription_status' => [
        'active' => 'Aktyvi',
        'expired' => 'Pasibaigusi',
        'suspended' => 'Sustabdyta',
        'cancelled' => 'Atšaukta',
    ],

    'user_assignment_action' => [
        'created' => 'Sukurta',
        'assigned' => 'Priskirta',
        'reassigned' => 'Priskirta iš naujo',
        'deactivated' => 'Deaktyvuota',
        'reactivated' => 'Aktyvuota iš naujo',
    ],

    'system_tenant_status' => [
        'active' => 'Aktyvus',
        'suspended' => 'Sustabdytas',
        'pending' => 'Laukiantis',
        'cancelled' => 'Atšauktas',
    ],

    'system_subscription_plan' => [
        'starter' => 'Pradedantysis',
        'professional' => 'Profesionalus',
        'enterprise' => 'Įmonės',
        'custom' => 'Individualus',
    ],

    'super_admin_audit_action' => [
        'system_tenant_created' => 'Sistemos nuomininkas sukurtas',
        'system_tenant_updated' => 'Sistemos nuomininkas atnaujintas',
        'system_tenant_suspended' => 'Sistemos nuomininkas sustabdytas',
        'system_tenant_activated' => 'Sistemos nuomininkas aktyvuotas',
        'system_tenant_deleted' => 'Sistemos nuomininkas ištrintas',
        'user_impersonated' => 'Vartotojas apsimetas',
        'impersonation_ended' => 'Apsimetimas baigtas',
        'bulk_operation' => 'Masinis veiksmas',
        'system_config_changed' => 'Sistemos konfigūracija pakeista',
        'backup_created' => 'Atsarginė kopija sukurta',
        'backup_restored' => 'Atsarginė kopija atkurta',
        'notification_sent' => 'Pranešimas išsiųstas',
        'feature_flag_changed' => 'Funkcijos žymė pakeista',
        'security_policy_changed' => 'Saugumo politika pakeista',
    ],

    'distribution_method' => [
        'equal' => 'Vienodas paskirstymas',
        'area' => 'Paskirstymas pagal plotą',
        'by_consumption' => 'Paskirstymas pagal suvartojimą',
        'custom_formula' => 'Paskirstymas pagal formulę',
        'equal_description' => 'Išlaidos paskirstomos vienodai visiems objektams',
        'area_description' => 'Išlaidos paskirstomos proporcingai pagal objekto plotą',
        'by_consumption_description' => 'Išlaidos paskirstomos pagal faktinio suvartojimo santykius',
        'custom_formula_description' => 'Naudojama individuali matematinė formulė paskirstymui',
    ],

    'pricing_model' => [
        'fixed_monthly' => 'Fiksuotas mėnesinis mokestis',
        'consumption_based' => 'Kainodara pagal suvartojimą',
        'tiered_rates' => 'Pakopinė tarifų struktūra',
        'hybrid' => 'Hibridinis kainodaros modelis',
        'custom_formula' => 'Individuali formulės kainodara',
        'flat' => 'Fiksuotas tarifas',
        'time_of_use' => 'Laiko zonų kainodara',
        'fixed_monthly_description' => 'Fiksuotas mėnesinis mokestis nepriklausomai nuo suvartojimo',
        'consumption_based_description' => 'Kainodara pagal faktinį suvartojimo kiekį',
        'tiered_rates_description' => 'Skirtingi tarifai skirtingiems suvartojimo lygiams',
        'hybrid_description' => 'Fiksuoto mokesčio ir suvartojimo pagrindu kainodaros derinys',
        'custom_formula_description' => 'Individuali matematinė formulė kainos apskaičiavimui',
        'flat_description' => 'Vienodas tarifas visam suvartojimui',
        'time_of_use_description' => 'Skirtingi tarifai skirtingu paros metu',
    ],

    'input_method' => [
        'manual' => 'Rankinis įvedimas',
        'photo_ocr' => 'Nuotrauka su OCR',
        'csv_import' => 'CSV importas',
        'api_integration' => 'API integracija',
        'estimated' => 'Įvertintas rodmuo',
        'manual_description' => 'Rankiniu būdu įvestas vartotojo',
        'photo_ocr_description' => 'Išgautas iš skaitiklio nuotraukos naudojant OCR',
        'csv_import_description' => 'Importuotas iš CSV failo',
        'api_integration_description' => 'Gautas per API integraciją',
        'estimated_description' => 'Įvertintas pagal istorinius duomenis',
    ],

    'validation_status' => [
        'pending' => 'Laukiama patvirtinimo',
        'validated' => 'Patvirtinta',
        'rejected' => 'Atmesta',
        'requires_review' => 'Reikia peržiūros',
        'pending_description' => 'Laukiama patvirtinimo',
        'validated_description' => 'Patvirtinta ir paruošta apskaičiavimui',
        'rejected_description' => 'Atmesta dėl klaidų ar neatitikimų',
        'requires_review_description' => 'Reikalinga rankinė peržiūra prieš patvirtinimą',
    ],

    'area_type' => [
        'total_area' => 'Bendras plotas',
        'heated_area' => 'Šildomas plotas',
        'commercial_area' => 'Komercinis plotas',
    ],

    'gyvatukas_calculation_type' => [
        'summer' => 'Vasaros apskaičiavimas',
        'winter' => 'Žiemos apskaičiavimas',
    ],
];
