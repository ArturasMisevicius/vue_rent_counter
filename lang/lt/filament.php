<?php

declare(strict_types=1);

return [
    'widgets' => [
        'activity_details' => [
            'title' => 'Veiksmo informacija',
            'action' => 'Veiksmas',
            'resource' => 'Išteklius',
            'user' => 'Naudotojas',
            'organization' => 'Organizacija',
            'timestamp' => 'Laiko žyma',
            'ip_address' => 'IP adresas',
            'changes' => 'Pakeitimai',
            'before' => 'Prieš',
            'after' => 'Po',
            'system' => 'Sistema',
            'not_available' => 'N/D',
        ],
    ],
    'resources' => [
        'subscription_usage' => [
            'usage_title' => 'Išteklių naudojimas',
            'properties' => 'Objektai',
            'tenants' => 'Nuomininkai',
            'approaching_limit' => '⚠️ Artėjama prie limito - apsvarstykite plano atnaujinimą',
            'subscription_details' => 'Prenumeratos duomenys',
            'plan_type' => 'Plano tipas',
            'status' => 'Būsena',
            'start_date' => 'Pradžios data',
            'expiry_date' => 'Pabaigos data',
            'days_left' => '(liko :days d.)',
            'expires_today' => '(baigiasi šiandien)',
            'expired_days_ago' => '(baigėsi prieš :days d.)',
            'limit_warning_title' => 'Išteklių limito įspėjimas',
            'limit_warning_body' => 'Šiai organizacijai artėja prenumeratos limitai. Apsvarstykite plano atnaujinimą, kad išvengtumėte paslaugos sutrikimų.',
        ],
    ],
    'pages' => [
        'dashboard' => [
            'welcome' => 'Sveiki sugrįžę, :name!',
            'admin_description' => 'Iš šio valdymo skydelio tvarkykite objektus, nuomininkus ir atsiskaitymus.',
            'manager_description' => 'Stebėkite skaitiklių rodmenis, sąskaitas ir objektų veiklą.',
            'tenant_description' => 'Peržiūrėkite savo objektą, skaitiklių rodmenis ir sąskaitas.',
            'quick_actions' => 'Greiti veiksmai',
            'cards' => [
                'properties' => [
                    'title' => 'Objektai',
                    'description' => 'Tvarkykite objektus',
                ],
                'buildings' => [
                    'title' => 'Pastatai',
                    'description' => 'Tvarkykite pastatus',
                ],
                'invoices' => [
                    'title' => 'Sąskaitos',
                    'description' => 'Tvarkykite sąskaitas',
                ],
                'users' => [
                    'title' => 'Vartotojai',
                    'description' => 'Tvarkykite vartotojus',
                ],
            ],
            'recent_activity_title' => 'Paskutinė veikla',
            'recent_activity_body' => 'Veiklos stebėsena netrukus bus pasiekiama...',
        ],
        'privacy' => [
            'title' => 'Privatumo politika',
            'last_updated' => 'Atnaujinta: :date',
            'sections' => [
                'introduction' => [
                    'title' => '1. Įžanga',
                    'body' => 'Šioje privatumo politikoje aprašoma, kaip renkame, naudojame ir saugome jūsų asmeninę informaciją naudojantis mūsų turto administravimo ir atsiskaitymų sistema. Esame įsipareigoję užtikrinti jūsų privatumą ir laikytis taikomų duomenų apsaugos įstatymų, įskaitant Bendrąjį duomenų apsaugos reglamentą (BDAR).',
                ],
                'information' => [
                    'title' => '2. Renkama informacija',
                    'subsections' => [
                        [
                            'title' => '2.1 Asmens duomenys',
                            'body' => 'Renkame šiuos asmens duomenų tipus:',
                            'items' => [
                                '<strong>Paskyros informacija:</strong> vardas, el. paštas, slaptažodis (užšifruotas), rolė ir organizacijos duomenys',
                                '<strong>Objekto informacija:</strong> adresai, tipai ir plotai',
                                '<strong>Atsiskaitymų informacija:</strong> skaitiklių rodmenys, sąskaitos, apmokėjimo būsena ir atsiskaitymo laikotarpiai',
                                '<strong>Naudojimo duomenys:</strong> prisijungimo laikas, IP adresai ir sistemos veiklos žurnalai',
                            ],
                        ],
                        [
                            'title' => '2.2 Automatiškai renkami duomenys',
                            'body' => 'Naudojantis sistema automatiškai renkame:',
                            'items' => [
                                'IP adresą ir naršyklės informaciją',
                                'Sesijos duomenis ir autentifikacijos raktus',
                                'Sistemos žurnalus ir audito įrašus',
                            ],
                        ],
                    ],
                ],
                'usage' => [
                    'title' => '3. Kaip naudojame jūsų informaciją',
                    'body' => 'Asmens duomenis naudojame šioms reikmėms:',
                    'items' => [
                        '<strong>Paslaugų teikimas:</strong> turto administravimo ir atsiskaitymų paslaugoms teikti',
                        '<strong>Autentifikavimas:</strong> tapatybei patikrinti ir prieigai valdyti',
                        '<strong>Atsiskaitymai:</strong> sąskaitoms generuoti, mokėjimams sekti ir skaitikliams tvarkyti',
                        '<strong>Komunikacija:</strong> svarbiems pranešimams ir naujinimams siųsti',
                        '<strong>Saugumas:</strong> neleistinai prieigai stebėti ir sistemos saugumui palaikyti',
                        '<strong>Atitiktis:</strong> laikytis teisinių ir reguliacinių reikalavimų',
                    ],
                ],
                'sharing' => [
                    'title' => '4. Duomenų dalijimas ir atskleidimas',
                    'body' => 'Mes neparduodame ir neperduodame jūsų duomenų trečiosioms šalims. Dalijamės informacija tik šiais atvejais:',
                    'items' => [
                        '<strong>Paslaugų teikėjai:</strong> patikimi tiekėjai, padedantys eksploatuoti sistemą',
                        '<strong>Teisiniai reikalavimai:</strong> kai to reikalauja įstatymai ar siekiant apginti teisėtus interesus',
                        '<strong>Verslo sandoriai:</strong> susiję su susijungimu, įsigijimu ar turto perdavimu',
                        '<strong>Su jūsų sutikimu:</strong> kai aiškiai sutikote su dalijimusi',
                    ],
                ],
                'security' => [
                    'title' => '5. Duomenų saugumas',
                    'body' => 'Taikome technines ir organizacines priemones jūsų duomenims apsaugoti, įskaitant:',
                    'items' => [
                        'Slaptažodžių ir jautrių duomenų šifravimą',
                        'Saugų autentifikavimą ir sesijų valdymą',
                        'Reguliarius saugumo auditus ir naujinimus',
                        'Prieigos kontrolę ir rolėmis paremtas teises',
                        'Visų prieigų ir pakeitimų audito žurnalus',
                    ],
                ],
                'retention' => [
                    'title' => '6. Duomenų saugojimas',
                    'body' => 'Asmens duomenis saugome tiek, kiek būtina:',
                    'items' => [
                        'Teikti jums paslaugas',
                        'Vykdyti teisines prievoles',
                        'Spręsti ginčus ir vykdyti sutartis',
                        'Palaikyti saugumo ir atitikties įrašus',
                    ],
                    'note' => 'Nebereikalingi duomenys saugiai ištrinami arba anonimizuojami pagal mūsų saugojimo politiką.',
                ],
                'rights' => [
                    'title' => '7. Jūsų teisės',
                    'body' => 'Pagal BDAR ir kitus įstatymus turite šias teises:',
                    'items' => [
                        '<strong>Teisė susipažinti:</strong> gauti savo duomenų kopiją',
                        '<strong>Teisė ištaisyti:</strong> pataisyti netikslius ar neišsamius duomenis',
                        '<strong>Teisė ištrinti:</strong> prašyti ištrinti savo asmens duomenis',
                        '<strong>Teisė apriboti:</strong> apriboti, kaip naudojame jūsų duomenis',
                        '<strong>Duomenų perkeliamumas:</strong> gauti duomenis struktūruotu formatu',
                        '<strong>Teisė nesutikti:</strong> nesutikti su tam tikru duomenų tvarkymu',
                        '<strong>Teisė atšaukti sutikimą:</strong> atšaukti duotą sutikimą',
                    ],
                    'note' => 'Norėdami pasinaudoti teisėmis, susisiekite su mumis 9 skyriuje pateiktais kontaktais.',
                ],
                'cookies' => [
                    'title' => '8. Slapukai ir stebėjimas',
                    'body' => 'Naudojame sesijos slapukus ir autentifikacijos žetonus prisijungimo sesijai palaikyti ir sistemos saugumui užtikrinti. Jie būtini sistemos veikimui ir negali būti išjungti.',
                ],
                'contact' => [
                    'title' => '9. Susisiekite',
                    'body' => 'Jei turite klausimų dėl privatumo politikos ar norite pasinaudoti savo teisėmis, susisiekite:',
                    'items' => [
                        '<strong>El. paštas:</strong> privacy@example.com',
                        '<strong>Adresas:</strong> [Jūsų įmonės adresas]',
                    ],
                ],
                'changes' => [
                    'title' => '10. Politikos pakeitimai',
                    'body' => 'Galime periodiškai atnaujinti šią privatumo politiką. Apie reikšmingus pakeitimus informuosime paskelbdami naują versiją ir atnaujindami datą „Atnaujinta“.',
                ],
            ],
        ],
        'terms' => [
            'title' => 'Paslaugų teikimo sąlygos',
            'last_updated' => 'Atnaujinta: :date',
            'sections' => [
                'acceptance' => [
                    'title' => '1. Sąlygų priėmimas',
                    'body' => 'Naudodamiesi šia turto administravimo ir atsiskaitymų sistema, sutinkate laikytis šių sąlygų. Jei nesutinkate, nesinaudokite sistema.',
                ],
                'description' => [
                    'title' => '2. Paslaugų aprašymas',
                    'body' => 'Sistema teikia turto administravimo ir komunalinių paslaugų atsiskaitymų funkcijas, įskaitant:',
                    'items' => [
                        'Objektų ir pastatų valdymą',
                        'Skaitiklių rodmenų sekimą ir valdymą',
                        'Sąskaitų generavimą ir atsiskaitymus',
                        'Nuomininkų ir vartotojų valdymą',
                        'Ataskaitas ir analitiką',
                    ],
                ],
                'accounts' => [
                    'title' => '3. Paskyros',
                    'subsections' => [
                        [
                            'title' => '3.1 Paskyros sukūrimas',
                            'body' => 'Norint naudotis sistema, reikia sukurti paskyrą su tikslia ir pilna informacija. Atsakote už prisijungimo duomenų konfidencialumą.',
                        ],
                        [
                            'title' => '3.2 Paskyros saugumas',
                            'body' => 'Jūs atsakote už:',
                            'items' => [
                                'Savo slaptažodžio saugumą',
                                'Visus veiksmus, atliekamus jūsų paskyroje',
                                'Nedelsiant pranešti apie neleistiną prieigą',
                            ],
                        ],
                        [
                            'title' => '3.3 Paskyros nutraukimas',
                            'body' => 'Galime sustabdyti ar nutraukti paskyrą, jei pažeidžiate šias sąlygas ar vykdote sukčiavimą, neteisėtą ar žalingą veiklą.',
                        ],
                    ],
                ],
                'acceptable_use' => [
                    'title' => '4. Tinkamas naudojimas',
                    'body' => 'Įsipareigojate nedaryti:',
                    'items' => [
                        'Nenaudoti sistemos neteisėtiems ar neleistiniems tikslams',
                        'Nebandyti gauti neleistinos prieigos prie sistemos ar kitų paskyrų',
                        'Netrikdyti sistemos saugumo ar funkcionalumo',
                        'Neįkelti kenkėjiško kodo, virusų ar žalingo turinio',
                        'Nesiskelti kitu asmeniu ar organizacija',
                        'Nepažeisti galiojančių įstatymų ar reglamentų',
                        'Be leidimo nepasiekti kitų nuomininkų ar organizacijų duomenų',
                    ],
                ],
                'accuracy' => [
                    'title' => '5. Duomenų tikslumas',
                    'body' => 'Esate atsakingi, kad į sistemą įvedami duomenys būtų tikslūs, išsamūs ir nuolat atnaujinami. Nesame atsakingi už jūsų pateiktas klaidas ar praleidimus.',
                ],
                'intellectual_property' => [
                    'title' => '6. Intelektinė nuosavybė',
                    'body' => 'Sistema ir visas jos turinys bei funkcijos priklauso mums ir yra saugomi autorių teisių, prekių ženklų ir kitais intelektinės nuosavybės įstatymais. Be raštiško leidimo negalite atkurti, platinti ar kurti išvestinių darbų.',
                ],
                'availability' => [
                    'title' => '7. Paslaugų prieinamumas',
                    'body' => 'Stengiamės užtikrinti aukštą prieinamumą, tačiau negarantuojame nepertraukiamo veikimo. Turime teisę:',
                    'items' => [
                        'Vykdyti planinius techninius darbus',
                        'Diegti sistemos naujinimus ir patobulinimus',
                        'Sustabdyti paslaugą esant saugumo grėsmėms ar techninėms problemoms',
                    ],
                ],
                'backup' => [
                    'title' => '8. Atsarginės kopijos ir atkūrimas',
                    'body' => 'Nors atliekame atsargines kopijas ir saugome duomenis, jūs patys atsakote už kritinių duomenų kopijas. Nesiimame atsakomybės už duomenų praradimą dėl sistemos gedimų, vartotojo klaidų ar kitų priežasčių.',
                ],
                'liability' => [
                    'title' => '9. Atsakomybės apribojimas',
                    'body' => 'Kiek leidžia įstatymai, neatsakome už netiesioginę, atsitiktinę, specialią ar baudžiamąją žalą, įskaitant pelno, duomenų ar verslo praradimą, atsiradusį dėl sistemos naudojimo.',
                ],
                'indemnification' => [
                    'title' => '10. Žalos atlyginimas',
                    'body' => 'Įsipareigojate atlyginti nuostolius ir apsaugoti mus nuo pretenzijų, žalos ar išlaidų (įskaitant teisines), kylančių dėl jūsų naudojimosi sistema, šių sąlygų pažeidimo ar kitų asmenų teisių pažeidimo.',
                ],
                'privacy' => [
                    'title' => '11. Privatumas',
                    'body' => 'Naudojimąsi sistema reguliuoja ir mūsų privatumo politika. Perskaitykite ją, kad suprastumėte, kaip renkame, naudojame ir saugome informaciją.',
                ],
                'modifications' => [
                    'title' => '12. Pakeitimai',
                    'body' => 'Paslaugų sąlygas galime keisti bet kada. Apie svarbius pakeitimus informuosime, o tolesnis sistemos naudojimas reikš sutikimą su atnaujintomis sąlygomis.',
                ],
                'law' => [
                    'title' => '13. Taikytina teisė',
                    'body' => 'Šioms sąlygoms taikomi ir jos aiškinamos pagal [Jūsų jurisdikcijos] teisę, nepaisant teisės normų kolizijos.',
                ],
                'contact' => [
                    'title' => '14. Kontaktai',
                    'body' => 'Jei turite klausimų dėl sąlygų, susisiekite:',
                    'items' => [
                        '<strong>El. paštas:</strong> support@example.com',
                        '<strong>Adresas:</strong> [Jūsų įmonės adresas]',
                    ],
                ],
            ],
        ],
        'gdpr' => [
            'title' => 'BDAR atitiktis',
            'last_updated' => 'Atnaujinta: :date',
            'sections' => [
                'overview' => [
                    'title' => '1. BDAR apžvalga',
                    'body' => 'Bendrasis duomenų apsaugos reglamentas (BDAR) – tai išsamus duomenų apsaugos įstatymas, įsigaliojęs 2018 m. gegužės 25 d. Jis taikomas visoms organizacijoms, tvarkančioms ES asmenų duomenis. Esame įsipareigoję laikytis BDAR reikalavimų.',
                ],
                'measures' => [
                    'title' => '2. Mūsų atitikties priemonės',
                    'subsections' => [
                        [
                            'title' => '2.1 Teisinis pagrindas',
                            'body' => 'Asmens duomenis tvarkome remdamiesi šiais teisiniais pagrindais:',
                            'items' => [
                                '<strong>Sutarties vykdymas:</strong> teikiant turto administravimo ir atsiskaitymų paslaugas',
                                '<strong>Teisinė prievolė:</strong> apskaitos ir mokesčių reikalavimams vykdyti',
                                '<strong>Pagrįsti interesai:</strong> sistemos saugumui, sukčiavimo prevencijai ir paslaugų tobulinimui',
                                '<strong>Sutikimas:</strong> kai jį aiškiai pateikia naudotojai',
                            ],
                        ],
                        [
                            'title' => '2.2 Duomenų minimizavimas',
                            'body' => 'Renkame ir tvarkome tik tuos asmens duomenis, kurie yra:',
                            'items' => [
                                'Būtini nurodytiems tikslams',
                                'Pakankami ir aktualūs mūsų paslaugoms',
                                'Apriboti iki to, kas reikalinga',
                            ],
                        ],
                        [
                            'title' => '2.3 Paskirties apribojimas',
                            'body' => 'Duomenys renkami aiškiais ir teisėtais tikslais ir nėra tvarkomi nesuderinamu būdu.',
                        ],
                        [
                            'title' => '2.4 Saugojimo apribojimas',
                            'body' => 'Duomenys laikomi ne ilgiau, nei būtina tikslams pasiekti. Turime saugojimo politiką su skirtingų duomenų tipų terminais.',
                        ],
                        [
                            'title' => '2.5 Tikslumas',
                            'body' => 'Dedame pastangas užtikrinti duomenų tikslumą ir atnaujinimą. Vartotojai gali atnaujinti informaciją sistemoje.',
                        ],
                        [
                            'title' => '2.6 Saugumas ir konfidencialumas',
                            'body' => 'Taikome technines ir organizacines priemones, kad užtikrintume:',
                            'items' => [
                                'Jautrių duomenų šifravimą perdavimo ir saugojimo metu',
                                'Prieigos kontrolę ir rolėmis paremtas teises',
                                'Reguliarius saugumo auditus ir pažeidžiamumo vertinimus',
                                'Darbuotojų mokymus apie duomenų apsaugą',
                                'Incidentų valdymo procedūras',
                            ],
                        ],
                        [
                            'title' => '2.7 Atskaitomybė',
                            'body' => 'Vediname duomenų tvarkymo dokumentaciją, įskaitant:',
                            'items' => [
                                'Tvarkymo veiklų įrašus',
                                'Poveikio duomenų apsaugai vertinimus',
                                'Saugumo incidentų žurnalus',
                                'Pranešimų apie duomenų pažeidimus procedūras',
                            ],
                        ],
                    ],
                ],
                'rights' => [
                    'title' => '3. Asmenų teisės pagal BDAR',
                    'body' => 'Gerbiame ir užtikriname šias teises:',
                    'subsections' => [
                        [
                            'title' => '3.1 Teisė susipažinti (15 str.)',
                            'body' => 'Turite teisę gauti patvirtinimą, ar tvarkome jūsų duomenis, ir susipažinti su jais bei jų tvarkymo informacija.',
                        ],
                        [
                            'title' => '3.2 Teisė ištaisyti (16 str.)',
                            'body' => 'Turite teisę ištaisyti netikslius ir papildyti neišsamius duomenis.',
                        ],
                        [
                            'title' => '3.3 Teisė būti pamirštam (17 str.)',
                            'body' => 'Turite teisę reikalauti ištrinti duomenis, kai:',
                            'items' => [
                                'Duomenys nebereikalingi pirminiam tikslui',
                                'Atšaukiate sutikimą ir nėra kito pagrindo',
                                'Duomenys tvarkomi neteisėtai',
                                'Ištrynimas būtinas teisinei prievolei vykdyti',
                            ],
                        ],
                        [
                            'title' => '3.4 Teisė apriboti tvarkymą (18 str.)',
                            'body' => 'Tam tikromis aplinkybėmis galite apriboti duomenų tvarkymą, pavyzdžiui, ginčydami tikslumą ar nesutikdami su tvarkymu.',
                        ],
                        [
                            'title' => '3.5 Duomenų perkeliamumas (20 str.)',
                            'body' => 'Turite teisę gauti savo duomenis struktūruotu, įprastu ir kompiuterio skaitomu formatu ir perduoti kitam valdytojui.',
                        ],
                        [
                            'title' => '3.6 Teisė nesutikti (21 str.)',
                            'body' => 'Turite teisę nesutikti, kad duomenys būtų tvarkomi teisėto intereso ar tiesioginės rinkodaros pagrindu.',
                        ],
                        [
                            'title' => '3.7 Sprendimai vien automatizuotu būdu (22 str.)',
                            'body' => 'Turite teisę nebūti sprendimų, priimamų vien automatizuotu būdu, įskaitant profiliavimą, objektu, jei jie sukelia teisines ar panašias reikšmingas pasekmes.',
                        ],
                    ],
                ],
                'records' => [
                    'title' => '4. Duomenų tvarkymo įrašai',
                    'body' => 'Laikome šių duomenų tvarkymo įrašus:',
                    'items' => [
                        'Tvarkomų asmens duomenų kategorijas',
                        'Tvarkymo tikslus',
                        'Duomenų subjektų kategorijas',
                        'Asmens duomenų gavėjus',
                        'Saugojimo terminus',
                        'Taikomas saugumo priemones',
                    ],
                ],
                'breach' => [
                    'title' => '5. Pranešimai apie pažeidimus',
                    'body' => 'Jei įvyktų didelės rizikos asmens duomenų pažeidimas, mes:',
                    'items' => [
                        'Per 72 val. informuosime kompetentingą priežiūros instituciją',
                        'Nedelsiant informuosime paveiktus asmenis',
                        'Pateiksime aiškią informaciją apie pažeidimo pobūdį',
                        'Paaiškinsime galimas pasekmes ir taikytas priemones',
                    ],
                ],
                'dpo' => [
                    'title' => '6. Duomenų apsaugos pareigūnas',
                    'body' => 'Esame paskyrę duomenų apsaugos pareigūną (DAP), prižiūrintį BDAR atitiktį. Dėl klausimų kreipkitės:',
                    'items' => [
                        '<strong>El. paštas:</strong> dpo@example.com',
                        '<strong>Adresas:</strong> [Jūsų įmonės adresas]',
                    ],
                ],
                'processors' => [
                    'title' => '7. Trečiųjų šalių tvarkytojai',
                    'body' => 'Naudodami išorinius paslaugų teikėjus duomenims tvarkyti užtikriname, kad jie:',
                    'items' => [
                        'Suteiktų pakankamas duomenų apsaugos garantijas',
                        'Būtų saistomi duomenų tvarkymo sutarčių',
                        'Laikytųsi BDAR reikalavimų',
                        'Įgyvendintų tinkamas saugumo priemones',
                    ],
                ],
                'transfers' => [
                    'title' => '8. Tarptautinis perdavimas',
                    'body' => 'Jei duomenys perduodami už ES/EEE ribų, užtikriname tinkamas apsaugos priemones, pavyzdžiui:',
                    'items' => [
                        'Standartines sutarčių sąlygas (SCC)',
                        'Europos Komisijos adekvatumo sprendimus',
                        'Privalomas įmonių taisykles (BCR)',
                    ],
                ],
                'exercising' => [
                    'title' => '9. Teisių įgyvendinimas',
                    'body' => 'Norėdami pasinaudoti savo BDAR teisėmis, susisiekite:',
                    'items' => [
                        '<strong>El. paštas:</strong> privacy@example.com',
                        '<strong>Temos eilutė:</strong> „GDPR Request - [Jūsų prašymo tipas]“',
                    ],
                    'note' => 'Į prašymus atsakome per vieną mėnesį. Sudėtingais atvejais šis terminas gali būti pratęstas dar dviem mėnesiais – apie tai informuosime.',
                ],
                'authority' => [
                    'title' => '10. Priežiūros institucija',
                    'body' => 'Jei manote, kad tinkamai neišsprendėme duomenų apsaugos klausimo, turite teisę pateikti skundą vietos priežiūros institucijai. ES gyventojai institucijų sąrašą ras čia: <a href="https://edpb.europa.eu/about-edpb/board/members_en" target="_blank" rel="noopener">European Data Protection Board</a>',
                ],
                'updates' => [
                    'title' => '11. Politikos atnaujinimai',
                    'body' => 'BDAR atitikties informaciją galime atnaujinti dėl praktikos ar teisinių reikalavimų pokyčių. Apie svarbius pakeitimus informuosime naudotojus.',
                ],
            ],
        ],
    ],
];
