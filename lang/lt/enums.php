<?php

declare(strict_types=1);

return [
    'area_type' => [
        'commercial_area' => 'Komercinė zona',
        'heated_area' => 'Šildomas plotas',
        'total_area' => 'Bendras plotas',
    ],
    'distribution_method' => [
        'area' => 'Plotas',
        'area_description' => 'Teritorijos aprašymas',
        'by_consumption' => 'Pagal vartojimą',
        'by_consumption_description' => 'Pagal vartojimo aprašymą',
        'custom_formula' => 'Pasirinktinė formulė',
        'custom_formula_description' => 'Individualizuotos formulės aprašymas',
        'equal' => 'Lygus',
        'equal_description' => 'Lygus aprašymas',
    ],
    'input_method' => [
        'api_integration_description' => 'Api integracijos aprašymas',
        'csv_import_description' => 'Csv importavimo aprašymas',
        'estimated_description' => 'Numatomas aprašymas',
        'manual_description' => 'Rankinis aprašymas',
        'photo_ocr_description' => 'Nuotraukų Ocr aprašymas',
    ],
    'pricing_model' => [
        'consumption_based_description' => 'Vartojimu pagrįstas aprašymas',
        'custom_formula_description' => 'Individualizuotos formulės aprašymas',
        'fixed_monthly_description' => 'Fiksuotas mėnesinis aprašymas',
        'flat_description' => 'Buto aprašymas',
        'hybrid_description' => 'Hibridinis aprašymas',
        'tiered_rates_description' => 'Pakopų tarifų aprašymas',
        'time_of_use_description' => 'Naudojimo laiko aprašymas',
    ],
    'service_type' => [
        'electricity' => 'Elektra',
        'heating' => 'Šildymas',
        'water' => 'Vanduo',
        'gas' => 'Dujos',
    ],
    'super_admin_audit_action' => [
        'backup_created' => 'Atsarginė kopija sukurta',
        'backup_restored' => 'Atkurta atsarginė kopija',
        'bulk_operation' => 'Masinis veikimas',
        'feature_flag_changed' => 'Funkcijos vėliavėlė pakeista',
        'impersonation_ended' => 'Apsimetinėjimas baigėsi',
        'notification_sent' => 'Pranešimas išsiųstas',
        'security_policy_changed' => 'Pakeista saugumo politika',
        'system_config_changed' => 'Pakeista sistemos konfigūracija',
        'system_tenant_activated' => 'Sistemos nuomininkas suaktyvintas',
        'system_tenant_created' => 'Sukurtas sistemos nuomininkas',
        'system_tenant_deleted' => 'Sistemos nuomininkas ištrintas',
        'system_tenant_suspended' => 'Sistemos nuomininkas sustabdytas',
        'system_tenant_updated' => 'Sistemos nuomininkas atnaujintas',
        'user_impersonated' => 'Vartotojas apsimetė',
    ],
    'system_subscription_plan' => [
        'custom' => 'Pasirinktinis',
        'enterprise' => 'Įmonė',
        'professional' => 'Profesionalus',
        'starter' => 'Starteris',
    ],
    'system_tenant_status' => [
        'active' => 'Aktyvus',
        'cancelled' => 'Atšaukta',
        'pending' => 'Laukiama',
        'suspended' => 'Sustabdytas',
    ],
    'user_role' => [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'manager' => 'Vadovas',
        'tenant' => 'Nuomininkas',
    ],
    'validation_status' => [
        'pending_description' => 'Laukiama aprašymo',
        'rejected_description' => 'Aprašymas atmestas',
        'requires_review_description' => 'Reikalingas peržiūros aprašymas',
        'validated_description' => 'Patvirtintas aprašymas',
    ],
];
