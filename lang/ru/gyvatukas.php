<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gyvatukas Language Lines (Russian)
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'building' => 'здание',
        'billing_month' => 'расчетный месяц',
        'distribution_method' => 'метод распределения',
    ],

    'validation' => [
        'building_required' => 'Здание обязательно.',
        'building_not_found' => 'Здание не найдено.',
        'no_properties' => 'В здании должна быть хотя бы одна квартира.',
        'unauthorized_building' => 'У вас нет прав для расчета этого здания.',
        
        'billing_month_required' => 'Расчетный месяц обязателен.',
        'billing_month_invalid' => 'Расчетный месяц должен быть действительной датой.',
        'billing_month_future' => 'Расчетный месяц не может быть в будущем.',
        'billing_month_too_old' => 'Расчетный месяц слишком давний.',
        
        'distribution_method_invalid' => 'Метод распределения должен быть "equal" или "area".',
    ],

    'errors' => [
        'unauthorized' => 'У вас нет прав для выполнения этого расчета.',
        'rate_limit_exceeded' => 'Слишком много расчетов. Попробуйте позже.',
        'invalid_configuration' => 'Неверная конфигурация gyvatukas. Обратитесь в поддержку.',
        'calculation_failed' => 'Расчет не удался. Попробуйте еще раз.',
    ],

    'messages' => [
        'calculation_complete' => 'Расчет gyvatukas успешно завершен.',
        'audit_created' => 'Запись аудита расчета создана.',
    ],
];
