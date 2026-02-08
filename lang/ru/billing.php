<?php

declare(strict_types=1);

return [
    'fields' => [
        'period_end' => 'Конец периода',
        'period_start' => 'Начало периода',
        'tenant' => 'Жилец',
    ],
    'validation' => [
        'duplicate_invoice' => 'Дубликат счета',
        'period_end_future' => 'Конец периода Будущее',
        'period_end_required' => 'Требуется конец периода',
        'period_start_future' => 'Период Начало Будущее',
        'period_start_required' => 'Требуется начало периода',
        'period_too_long' => 'Слишком длинный период',
        'tenant_inactive' => 'Арендатор неактивен',
        'tenant_not_found' => 'Арендатор не найден',
        'tenant_required' => 'Требуется арендатор',
    ],
];
