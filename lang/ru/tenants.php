<?php

declare(strict_types=1);

return [
    'headings' => [
        'index' => 'Учетные записи арендаторов',
        'index_description' => 'Управляйте учетными записями арендаторов и назначением объектов',
        'list' => 'Список арендаторов',
        'show' => 'Данные арендатора',
        'account' => 'Информация об учетной записи',
        'current_property' => 'Текущий объект',
        'assignment_history' => 'История назначений',
        'recent_readings' => 'Недавние показания',
        'recent_invoices' => 'Недавние счета',
    ],

    'actions' => [
        'deactivate' => 'Деактивировать',
        'reactivate' => 'Активировать',
        'reassign' => 'Переназначить объект',
        'add' => 'Добавить арендатора',
        'view' => 'Просмотр',
    ],

    'labels' => [
        'name' => 'Имя',
        'status' => 'Статус',
        'email' => 'Email',
        'created' => 'Создан',
        'created_by' => 'Создал',
        'address' => 'Адрес',
        'type' => 'Тип',
        'area' => 'Площадь',
        'reading' => 'Показание',
        'invoice' => 'Счет #:id',
        'reason' => 'Причина',
        'property' => 'Объект',
        'actions' => 'Действия',
    ],

    'statuses' => [
        'active' => 'Активен',
        'inactive' => 'Неактивен',
    ],

    'empty' => [
        'property' => 'Объект не назначен',
        'assignment_history' => 'История назначений отсутствует',
        'recent_readings' => 'Недавних показаний нет',
        'recent_invoices' => 'Недавних счетов нет',
        'list' => 'Арендаторы не найдены.',
        'list_cta' => 'Создайте первого арендатора',
    ],
];
