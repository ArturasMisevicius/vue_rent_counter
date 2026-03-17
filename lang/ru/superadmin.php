<?php

return [
    'organizations' => [
        'singular' => 'Организация',
        'plural' => 'Организации',
        'sections' => [
            'profile' => 'Профиль организации',
            'activity' => 'Сводка активности',
        ],
        'columns' => [
            'name' => 'Название',
            'slug' => 'Slug',
            'status' => 'Статус',
            'owner' => 'Владелец',
            'owner_email' => 'Email владельца',
            'users_count' => 'Пользователи',
            'properties_count' => 'Объекты',
            'subscriptions_count' => 'Подписки',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ],
        'empty' => [
            'owner' => 'Владелец не назначен',
        ],
        'status' => [
            'active' => 'Активна',
            'suspended' => 'Приостановлена',
        ],
    ],
];
