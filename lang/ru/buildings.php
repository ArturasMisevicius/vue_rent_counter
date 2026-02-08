<?php

return array (
  'errors' => 
  array (
    'has_properties' => 'Имеет свойства',
  ),
  'labels' => 
  array (
    'address' => 'Адрес',
    'building' => 'Здание',
    'buildings' => 'Здания',
    'created_at' => 'Создано в',
    'name' => 'Имя',
    'property_count' => 'Количество недвижимости',
    'total_apartments' => 'Всего квартир',
  ),
  'pages' => 
  array (
    'manager_form' => 
    array (
      'actions' => 
      array (
        'cancel' => 'Отмена',
        'save_create' => 'Сохранить Создать',
        'save_edit' => 'Сохранить Редактировать',
      ),
      'create_subtitle' => 'Создать субтитры',
      'create_title' => 'Создать заголовок',
      'edit_subtitle' => 'Редактировать субтитры',
      'edit_title' => 'Изменить заголовок',
      'labels' => 
      array (
        'address' => 'Адрес',
        'name' => 'Имя',
        'total_apartments' => 'Всего квартир',
      ),
      'placeholders' => 
      array (
        'address' => 'Адрес',
        'name' => 'Имя',
        'total_apartments' => 'Всего квартир',
      ),
    ),
    'manager_index' => 
    array (
      'add' => 'Добавлять',
      'create_now' => 'Создать сейчас',
      'description' => 'Описание',
      'empty' => 'Пустой',
      'headers' => 
      array (
        'actions' => 'Действия',
        'building' => 'Здание',
        'last_calculated' => 'Последний расчет',
        'properties' => 'Характеристики',
        'total_apartments' => 'Всего квартир',
      ),
      'mobile' => 
      array (
        'apartments' => 'Квартиры',
        'edit' => 'Редактировать',
        'last' => 'Последний',
        'properties' => 'Характеристики',
        'view' => 'Вид',
      ),
      'never' => 'Никогда',
      'not_calculated' => 'Не рассчитано',
      'table_caption' => 'Заголовок таблицы',
      'title' => 'Заголовок',
    ),
    'manager_show' => 
    array (
      'add_property' => 'Добавить недвижимость',
      'calculated' => 'Рассчитано',
      'delete_building' => 'Удалить здание',
      'delete_confirm' => 'Удалить Подтвердить',
      'description' => 'Описание',
      'edit_building' => 'Редактировать здание',
      'empty_properties' => 'Пустые свойства',
      'form' => 
      array (
        'end_date' => 'Дата окончания',
        'start_date' => 'Дата начала',
        'submit' => 'Представлять на рассмотрение',
      ),
      'info_title' => 'Информация Название',
      'labels' => 
      array (
        'address' => 'Адрес',
        'name' => 'Имя',
        'properties_registered' => 'Недвижимость зарегистрирована',
        'total_apartments' => 'Всего квартир',
      ),
      'last_calculated' => 'Последний расчет',
      'never' => 'Никогда',
      'not_calculated' => 'Не рассчитано',
      'pending' => 'В ожидании',
      'properties_headers' => 
      array (
        'actions' => 'Действия',
        'address' => 'Адрес',
        'area' => 'Область',
        'meters' => 'Метры',
        'tenant' => 'Жилец',
        'type' => 'Тип',
      ),
      'properties_title' => 'Название недвижимости',
      'status' => 'Статус',
      'summer_average' => 'Летний средний показатель',
      'title' => 'Заголовок',
      'vacant' => 'Вакантный',
      'view' => 'Вид',
    ),
    'show' => 
    array (
      'heading' => 'Заголовок',
      'title' => 'Заголовок',
    ),
  ),
  'validation' => 
  array (
    'address' => 
    array (
      'max' => 'Макс',
      'required' => 'Необходимый',
      'string' => 'Нить',
      'regex' => 'Address contains invalid characters.',
    ),
    'name' => 
    array (
      'max' => 'Макс',
      'required' => 'Необходимый',
      'string' => 'Нить',
      'regex' => 'Building name may only contain letters, numbers, spaces, hyphens, underscores, dots, and hash symbols.',
      'unique' => 'A building with this name already exists in your organization.',
    ),
    'tenant_id' => 
    array (
      'integer' => 'Целое число',
      'required' => 'Необходимый',
      'exists' => 'The selected organization does not exist.',
    ),
    'total_apartments' => 
    array (
      'integer' => 'Целое число',
      'max' => 'Макс',
      'min' => 'Мин',
      'required' => 'Необходимый',
    ),
    'city' => 
    array (
      'regex' => 'City name may only contain letters, spaces, and hyphens.',
    ),
    'postal_code' => 
    array (
      'regex' => 'Postal code format is invalid.',
    ),
    'country' => 
    array (
      'size' => 'Country code must be exactly 2 characters.',
    ),
    'built_year' => 
    array (
      'min' => 'Built year cannot be earlier than 1800.',
      'max' => 'Built year cannot be more than 5 years in the future.',
    ),
    'heating_type' => 
    array (
      'in' => 'The selected heating type is invalid.',
    ),
    'parking_spaces' => 
    array (
      'max' => 'Parking spaces cannot exceed 500.',
    ),
    'notes' => 
    array (
      'max' => 'Notes may not be greater than 2000 characters.',
    ),
    'apartments_per_floor_excessive' => 'The number of apartments per floor seems excessive. Please verify.',
    'central_heating_anachronistic' => 'Central heating was not common in buildings built before 1900.',
  ),
  'attributes' => 
  array (
    'tenant_id' => 'organization',
    'name' => 'building name',
    'address' => 'address',
    'city' => 'city',
    'postal_code' => 'postal code',
    'country' => 'country',
    'total_apartments' => 'total apartments',
    'floors' => 'floors',
    'built_year' => 'year built',
    'heating_type' => 'heating type',
    'elevator' => 'elevator',
    'parking_spaces' => 'parking spaces',
    'notes' => 'notes',
  ),
);
