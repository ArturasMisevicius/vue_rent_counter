<?php

declare(strict_types=1);

return [
    'labels' => [
        'name' => 'Pavadinimas',
        'address' => 'Adresas',
        'total_apartments' => 'Butų skaičius',
        'property_count' => 'Objektų skaičius',
        'created_at' => 'Sukurta',
        'building' => 'Здание',
        'buildings' => 'Здания',
    ],
    'validation' => [
        'tenant_id' => [
            'required' => 'Organizacija yra privaloma.',
            'integer' => 'Organizacijos ID turi būti skaičius.',
            'exists' => 'Pasirinkta organizacija neegzistuoja.',
        ],
        'name' => [
            'required' => 'Pastato pavadinimas yra privalomas.',
            'string' => 'Pastato pavadinimas turi būti tekstas.',
            'max' => 'Pastato pavadinimas negali būti ilgesnis nei 255 simboliai.',
            'regex' => 'Pastato pavadinime gali būti tik raidės, skaičiai, tarpai, brūkšneliai, pabraukimai, taškai ir grotelės simboliai.',
            'unique' => 'Pastatas su tokiu pavadinimu jau egzistuoja jūsų organizacijoje.',
        ],
        'address' => [
            'required' => 'Adresas yra privalomas.',
            'string' => 'Adresas turi būti tekstas.',
            'max' => 'Adresas negali būti ilgesnis nei 500 simbolių.',
            'regex' => 'Adrese yra netinkamų simbolių.',
        ],
        'city' => [
            'regex' => 'Miesto pavadinime gali būti tik raidės, tarpai ir brūkšneliai.',
        ],
        'postal_code' => [
            'regex' => 'Pašto kodo formatas yra netinkamas.',
        ],
        'country' => [
            'size' => 'Šalies kodas turi būti lygiai 2 simboliai.',
        ],
        'total_apartments' => [
            'required' => 'Bendras butų skaičius yra privalomas.',
            'integer' => 'Bendras butų skaičius turi būti skaičius.',
            'min' => 'Pastate turi būti bent 1 butas.',
            'max' => 'Pastate negali būti daugiau nei 1000 butų.',
        ],
        'built_year' => [
            'min' => 'Statybos metai negali būti ankstesni nei 1800.',
            'max' => 'Statybos metai negali būti daugiau nei 5 metai į ateitį.',
        ],
        'heating_type' => [
            'in' => 'Pasirinktas šildymo tipas yra netinkamas.',
        ],
        'parking_spaces' => [
            'max' => 'Parkavimo vietų negali būti daugiau nei 500.',
        ],
        'notes' => [
            'max' => 'Pastabos negali būti ilgesnės nei 2000 simbolių.',
        ],
        'apartments_per_floor_excessive' => 'Butų skaičius aukšte atrodo per didelis. Patikrinkite.',
        'central_heating_anachronistic' => 'Centrinis šildymas nebuvo paplitęs pastatuose, statytuose iki 1900 metų.',
    ],
    'attributes' => [
        'tenant_id' => 'organizacija',
        'name' => 'pastato pavadinimas',
        'address' => 'adresas',
        'city' => 'miestas',
        'postal_code' => 'pašto kodas',
        'country' => 'šalis',
        'total_apartments' => 'bendras butų skaičius',
        'floors' => 'aukštai',
        'built_year' => 'statybos metai',
        'heating_type' => 'šildymo tipas',
        'elevator' => 'liftas',
        'parking_spaces' => 'parkavimo vietos',
        'notes' => 'pastabos',
    ],
    'errors' => [
        'has_properties' => 'Имеет свойства',
    ],
    'pages' => [
        'manager_form' => [
            'actions' => [
                'cancel' => 'Отмена',
                'save_create' => 'Сохранить Создать',
                'save_edit' => 'Сохранить Редактировать',
            ],
            'create_subtitle' => 'Создать субтитры',
            'create_title' => 'Создать заголовок',
            'edit_subtitle' => 'Редактировать субтитры',
            'edit_title' => 'Изменить заголовок',
            'labels' => [
                'address' => 'Адрес',
                'name' => 'Имя',
                'total_apartments' => 'Всего квартир',
            ],
            'placeholders' => [
                'address' => 'Адрес',
                'name' => 'Имя',
                'total_apartments' => 'Всего квартир',
            ],
        ],
        'manager_index' => [
            'add' => 'Добавлять',
            'create_now' => 'Создать сейчас',
            'description' => 'Описание',
            'empty' => 'Пустой',
            'headers' => [
                'actions' => 'Действия',
                'building' => 'Здание',
                'last_calculated' => 'Последний расчет',
                'properties' => 'Характеристики',
                'total_apartments' => 'Всего квартир',
            ],
            'mobile' => [
                'apartments' => 'Квартиры',
                'edit' => 'Редактировать',
                'last' => 'Последний',
                'properties' => 'Характеристики',
                'view' => 'Вид',
            ],
            'never' => 'Никогда',
            'not_calculated' => 'Не рассчитано',
            'table_caption' => 'Заголовок таблицы',
            'title' => 'Заголовок',
        ],
        'manager_show' => [
            'add_property' => 'Добавить недвижимость',
            'calculated' => 'Рассчитано',
            'delete_building' => 'Удалить здание',
            'delete_confirm' => 'Удалить Подтвердить',
            'description' => 'Описание',
            'edit_building' => 'Редактировать здание',
            'empty_properties' => 'Пустые свойства',
            'form' => [
                'end_date' => 'Дата окончания',
                'start_date' => 'Дата начала',
                'submit' => 'Представлять на рассмотрение',
            ],
            'info_title' => 'Информация Название',
            'labels' => [
                'address' => 'Адрес',
                'name' => 'Имя',
                'properties_registered' => 'Недвижимость зарегистрирована',
                'total_apartments' => 'Всего квартир',
            ],
            'last_calculated' => 'Последний расчет',
            'never' => 'Никогда',
            'not_calculated' => 'Не рассчитано',
            'pending' => 'В ожидании',
            'properties_headers' => [
                'actions' => 'Действия',
                'address' => 'Адрес',
                'area' => 'Область',
                'meters' => 'Метры',
                'tenant' => 'Жилец',
                'type' => 'Тип',
            ],
            'properties_title' => 'Название недвижимости',
            'status' => 'Статус',
            'summer_average' => 'Летний средний показатель',
            'title' => 'Заголовок',
            'vacant' => 'Вакантный',
            'view' => 'Вид',
        ],
        'show' => [
            'heading' => 'Заголовок',
            'title' => 'Заголовок',
        ],
    ],
];
