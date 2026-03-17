<?php

return [
    'profile' => [
        'title' => 'Mano profilis',
        'description' => 'Tvarkykite savo asmeninę informaciją, pasirinktą kalbą ir prisijungimo duomenis viename paskyros puslapyje.',
        'personal_information' => 'Asmeninė informacija',
        'fields' => [
            'name' => 'Vardas',
            'email' => 'El. paštas',
            'locale' => 'Kalbos pasirinkimas',
        ],
        'language_preference' => 'Kalbos pasirinkimas',
        'security' => 'Saugumas',
        'change_password' => 'Keisti slaptažodį',
        'password_description' => 'Patvirtinkite pakeitimą dabartiniu slaptažodžiu ir pasirinkite naują kitam prisijungimui.',
        'current_password' => 'Dabartinis slaptažodis',
        'new_password' => 'Naujas slaptažodis',
        'confirm_password' => 'Pakartokite naują slaptažodį',
        'messages' => [
            'saved' => 'Profilio duomenys atnaujinti.',
            'password_updated' => 'Slaptažodis atnaujintas.',
        ],
    ],
    'settings' => [
        'title' => 'Nustatymai',
        'description' => 'Tvarkykite organizacijos atsiskaitymo, pranešimų ir prenumeratos nuostatas.',
        'organization' => [
            'title' => 'Organizacijos nustatymai',
            'description' => 'Atnaujinkite atsiskaitymo kontaktus ir sąskaitų kopiją visai organizacijai.',
            'billing_contact_name' => 'Atsiskaitymo kontakto vardas',
            'billing_contact_email' => 'Atsiskaitymo kontakto el. paštas',
            'billing_contact_phone' => 'Atsiskaitymo kontakto telefonas',
            'payment_instructions' => 'Mokėjimo instrukcijos',
            'invoice_footer' => 'Sąskaitos poraštė',
        ],
        'notifications' => [
            'title' => 'Pranešimų nuostatos',
            'description' => 'Pasirinkite, kurie operaciniai įspėjimai turi būti rodomi organizacijai.',
            'invoice_reminders' => 'Sąskaitų priminimai',
            'invoice_reminders_help' => 'Rodyti priminimus apie pradelstas sąskaitas ir artėjančius terminus.',
            'reading_deadline_alerts' => 'Rodmenų terminų įspėjimai',
            'reading_deadline_alerts_help' => 'Išryškinti skaitiklius, kuriems artėja kito rodmens pateikimo terminas.',
        ],
        'subscription' => [
            'title' => 'Prenumerata',
            'description' => 'Peržiūrėkite aktyvią komercinę būseną ir pratęskite planą neišeidami iš organizacijos darbo erdvės.',
            'plan' => 'Planas',
            'status' => 'Būsena',
            'expires_at' => 'Galioja iki',
            'duration' => 'Pratęsimo trukmė',
            'not_set' => 'Nenustatyta',
        ],
        'manager' => [
            'title' => 'Nustatymai',
            'description' => 'Organizacijos atsiskaitymo, pranešimų ir prenumeratos valdymą atlieka administratoriai. Paskyros duomenis keiskite profilio puslapyje.',
        ],
        'messages' => [
            'organization_saved' => 'Organizacijos nustatymai atnaujinti.',
            'notifications_saved' => 'Pranešimų nuostatos atnaujintos.',
            'subscription_renewed' => 'Prenumeratos pratęsimo duomenys atnaujinti.',
        ],
    ],
    'actions' => [
        'save_profile' => 'Išsaugoti profilį',
        'update_password' => 'Atnaujinti slaptažodį',
        'save_settings' => 'Išsaugoti nustatymus',
        'save_notifications' => 'Išsaugoti pranešimų nuostatas',
        'renew_subscription' => 'Pratęsti prenumeratą',
    ],
];
