<?php

declare(strict_types=1);

return [
    'navigation' => 'Переводы',
    'sections' => [
        'key' => 'Ключ перевода',
        'values' => 'Значения перевода',
    ],
    'labels' => [
        'group' => 'Группа',
        'key' => 'Ключ',
        'value' => 'Значение',
        'last_updated' => 'Последнее обновление',
    ],
    'placeholders' => [
        'group' => 'app',
        'key' => 'nav.dashboard',
        'value' => '—',
    ],
    'helper_text' => [
        'key' => 'Укажите группу и ключ для этого перевода',
        'group' => 'Имя PHP-файла в каталоге lang/{locale}/ (например, «app» для app.php)',
        'key_full' => 'Ключ перевода с точечной нотацией (например, «nav.dashboard»)',
        'values' => 'Укажите переводы для каждого активного языка. Значения записываются в PHP-файлы переводов.',
        'default_language' => 'Язык по умолчанию',
    ],
    'empty' => [
        'heading' => 'Переводов пока нет',
        'description' => 'Создайте записи переводов для управления многоязычным контентом.',
        'action' => 'Добавить первый перевод',
    ],
    'modals' => [
        'delete' => [
            'heading' => 'Удалить переводы',
            'description' => 'Вы уверены, что хотите удалить эти переводы? Это повлияет на интерфейс приложения.',
        ],
    ],
    'table' => [
        'value_label' => 'Значение для :locale',
        'language_label' => ':language (:code)',
    ],
];
