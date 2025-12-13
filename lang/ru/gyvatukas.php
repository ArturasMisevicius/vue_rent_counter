<?php

declare(strict_types=1);

return [
    'attributes' => [
        'billing_month' => 'Платежный месяц',
        'building' => 'Здание',
        'distribution_method' => 'Метод распространения',
    ],
    'validation' => [
        'billing_month_future' => 'Будущий месяц выставления счетов',
        'billing_month_invalid' => 'Неверный месяц выставления счета',
        'billing_month_required' => 'Требуется месяц выставления счета',
        'billing_month_too_old' => 'Месяц выставления счета слишком старый',
        'building_not_found' => 'Здание не найдено',
        'building_required' => 'Требуется здание',
        'distribution_method_invalid' => 'Неверный метод распространения',
        'no_properties' => 'Нет свойств',
        'unauthorized_building' => 'Несанкционированное здание',
    ],
];
