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
];
