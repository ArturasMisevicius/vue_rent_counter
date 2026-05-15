<?php

return [
    'title' => 'Pradėkite nemokamą bandomąjį laikotarpį',
    'subtitle' => 'Sukurkite savo organizaciją ir atrakinkite administratoriaus darbo erdvę.',
    'trial_badge' => '14 dienų nemokamas bandymas',
    'trial_message' => 'Iki jūsų Tenanto administratoriaus darbo erdvės liko vienas žingsnis. Sukurkite organizaciją dabar ir mes iš karto aktyvuosime jūsų nemokamą bandomąjį laikotarpį.',
    'organization_name_label' => 'Organizacijos pavadinimas',
    'submit_button' => 'Aktyvuoti nemokamą bandymą',
    'submit_button_loading' => 'Aktyvuojamas nemokamas bandymas...',
    'tour' => [
        'badge' => 'Sistemos gidas',
        'title' => 'Sveiki atvykę į savo darbo erdvę',
        'subtitle' => 'Trumpas gidas paaiškina, kur yra pagrindiniai įrankiai ir kaip naudoti kiekvieną sritį.',
        'progress_label' => 'Gido eiga',
        'step_count' => ':current žingsnis iš :total',
        'actions' => [
            'back' => 'Atgal',
            'close' => 'Uždaryti gidą',
            'finish' => 'Baigti',
            'later' => 'Vėliau',
            'next' => 'Toliau',
            'open' => 'Gidas',
        ],
        'roles' => [
            'admin' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Skydelis ir darbo erdvė',
                        'body' => 'Skydelis yra organizacijos darbo pradžios vieta. Jame matote būstus, sąskaitas, rodmenis ir prenumeratos limitus, kad pirmiausia pastebėtumėte svarbiausius darbus.',
                        'detail' => 'Naudokite jį kaip kasdienę apžvalgą prieš atidarydami detalius puslapius iš šoninio meniu.',
                    ],
                    'navigation' => [
                        'title' => 'Šoninis meniu, paieška ir kalba',
                        'body' => 'Kairysis meniu sugrupuoja būstų, atsiskaitymo, ataskaitų ir paskyros įrankius. Viršuje esanti paieška padeda greitai rasti įrašus, o kalbos pasirinkimas pakeičia jūsų paskyros sąsają.',
                        'detail' => 'Atidarykite įrašus iš meniu, kai žinote modulį, arba naudokite paiešką pagal vardą, numerį, nuomininką, sąskaitą ar skaitiklį.',
                    ],
                    'workflows' => [
                        'title' => 'Būstai ir nuomininkai',
                        'body' => 'Pastatai, būstai, nuomininkai, skaitikliai ir rodmenys yra susieti. Pradėkite nuo pastato arba būsto, tada peržiūrėkite nuomininkus, skaitiklius ir rodmenų istoriją susijusiuose puslapiuose.',
                        'detail' => 'Kurkite ir redaguokite duomenis modulių puslapiuose; prieš kurdami naują įrašą pirmiausia patikrinkite susijusį būstą.',
                    ],
                    'activity' => [
                        'title' => 'Atsiskaitymas ir ataskaitos',
                        'body' => 'Sąskaitos, tarifai, tiekėjai, paslaugų konfigūracijos ir komunalinės paslaugos valdo atsiskaitymą. Ataskaitos skirtos peržiūrai ir operaciniams patikrinimams.',
                        'detail' => 'Prieš generuodami arba peržiūrėdami sąskaitas įsitikinkite, kad tiekėjų ir tarifų nustatymai yra teisingi.',
                    ],
                    'profile' => [
                        'title' => 'Profilis, nustatymai ir vadybininkai',
                        'body' => 'Profilis skirtas jūsų asmeniniams duomenims. Nustatymuose valdoma organizacijos atsiskaitymo informacija, o Organizacijos naudotojai leidžia peržiūrėti vadybininkų prieigas.',
                        'detail' => 'Vadybininkų teisės ribojamos matrica, todėl suteikite tik tuos kūrimo, redagavimo ir šalinimo veiksmus, kuriuos jie turi atlikti.',
                    ],
                ],
            ],
            'manager' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Jums priskirta darbo erdvė',
                        'body' => 'Skydelyje matote organizacijos informaciją, kurią galite peržiūrėti. Administratorius nustato, kokius kūrimo, redagavimo ir šalinimo veiksmus galite atlikti.',
                        'detail' => 'Jei mygtuko nėra arba veiksmas blokuojamas, greičiausiai vadybininko teisių matricoje šis veiksmas nesuteiktas.',
                    ],
                    'navigation' => [
                        'title' => 'Meniu ir paieška',
                        'body' => 'Šoninis meniu leidžia pereiti į būstus, rodmenis, sąskaitas, tiekėjus ir ataskaitas. Paieška padeda greičiau atidaryti žinomus įrašus.',
                        'detail' => 'Meniu rodo tik tas sritis, kurias jūsų vaidmuo gali naudoti šioje organizacijoje.',
                    ],
                    'workflows' => [
                        'title' => 'Būstai, skaitikliai ir rodmenys',
                        'body' => 'Operaciniam darbui pradėkite nuo būsto. Prieš redaguodami skaitiklius ir rodmenis patikrinkite, ar jie priskirti teisingam būstui.',
                        'detail' => 'Teikdami rodmenis patvirtinkite skaitiklį, datą, reikšmę ir validavimo pranešimą prieš išsaugodami.',
                    ],
                    'activity' => [
                        'title' => 'Atsiskaitymas ir ataskaitos',
                        'body' => 'Atsiskaitymo puslapiai matomi tada, kai jūsų vadybininko teisės leidžia dirbti su atsiskaitymu. Ataskaitos skirtos peržiūrai ir tolesniems veiksmams.',
                        'detail' => 'Jei atsiskaitymas paslėptas, paprašykite administratoriaus suteikti reikalingas atsiskaitymo teises.',
                    ],
                    'profile' => [
                        'title' => 'Profilis ir paskyra',
                        'body' => 'Profilyje galite atnaujinti vardą, el. paštą, telefoną, slaptažodį, kalbą ir avatarą. Organizacijos nustatymai lieka administratoriams.',
                        'detail' => 'Laikykite profilį aktualų, kad pranešimai ir audito istorija jus identifikuotų teisingai.',
                    ],
                ],
            ],
            'tenant' => [
                'steps' => [
                    'workspace' => [
                        'title' => 'Nuomininko pradžia',
                        'body' => 'Pradžios puslapis rodo priskirto būsto informaciją, naujausius rodmenis, sąskaitų būseną ir greitus veiksmus įprastoms nuomininko užduotims.',
                        'detail' => 'Pradėkite čia, kai norite patikrinti pasikeitimus arba tęsti mėnesio užduotį.',
                    ],
                    'navigation' => [
                        'title' => 'Viršutinis ir mobilusis meniu',
                        'body' => 'Viršutiniame meniu yra Pradžia, Mano būstas, Rodmenys ir Sąskaitos. Mobiliajame ekrane atidarykite Meniu, kad pasiektumėte tuos pačius puslapius, paiešką, profilį ir atsijungimą.',
                        'detail' => 'Tas pats meniu rodomas kiekviename nuomininko puslapyje; aktyvus punktas parodo, kur esate dabar.',
                    ],
                    'workflows' => [
                        'title' => 'Rodmenų pateikimas',
                        'body' => 'Rodmenų puslapyje galite pasirinkti priskirtą skaitiklį, įvesti datą ir reikšmę, peržiūrėti suvartojimo pokytį ir viską pateikti viename sraute.',
                        'detail' => 'Prieš pateikdami perskaitykite validavimo pranešimus; jie paaiškina, kodėl reikšmė gali būti per maža, per didelė, pasikartojanti arba ne tame laikotarpyje.',
                    ],
                    'activity' => [
                        'title' => 'Sąskaitos ir būsto detalės',
                        'body' => 'Sąskaitose matote istoriją, apmokėjimo būseną, eilutes ir mokėjimo informaciją. Būsto detalėse rodoma jūsų priskirta patalpa ir pastato informacija.',
                        'detail' => 'Atidarykite sąskaitos detales, kai reikia sumų, datų, paslaugų arba atsiskaitymo kontaktų.',
                    ],
                    'profile' => [
                        'title' => 'Paskyra ir kalba',
                        'body' => 'Paskyros puslapyje atnaujinkite kontaktus, pasirinktą kalbą, slaptažodį ir avatarą. Kalbos pasirinkimas ten pakeičia nuomininko sąsajos tekstus.',
                        'detail' => 'Pakeitus kalbą arba avatarą, nuomininko meniu ir puslapiai atsinaujina pagal naujausius paskyros nustatymus.',
                    ],
                ],
            ],
        ],
    ],
];
