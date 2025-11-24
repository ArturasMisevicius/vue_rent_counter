<?php

declare(strict_types=1);

return [
    'subscription_expiry' => [
        'subject' => 'Предупреждение об окончании подписки',
        'greeting' => 'Здравствуйте, :name!',
        'intro' => 'Ваша подписка на Vilnius Utilities Billing System истекает через **:days дней**, :date.',
        'plan' => '**Текущий план:** :plan',
        'properties' => '**Объекты:** :used / :max',
        'tenants' => '**Арендаторы:** :used / :max',
        'cta_intro' => 'Чтобы избежать прерывания сервиса, продлите подписку до истечения срока.',
        'cta_notice' => 'После окончания действия доступ будет только для чтения до продления.',
        'action' => 'Продлить подписку',
        'support' => 'Если у вас есть вопросы по продлению, свяжитесь со службой поддержки.',
    ],

    'welcome' => [
        'subject' => 'Добро пожаловать в Vilnius Utilities Billing System',
        'greeting' => 'Здравствуйте, :name!',
        'account_created' => 'Для вас создана учетная запись арендатора по следующему объекту:',
        'address' => '**Адрес:** :address',
        'property_type' => '**Тип объекта:** :type',
        'credentials_heading' => '**Данные для входа:**',
        'email' => 'Email: :email',
        'temporary_password' => 'Временный пароль: :password',
        'password_reminder' => 'Войдите и сразу смените пароль.',
        'action' => 'Войти',
        'support' => 'Если у вас есть вопросы, свяжитесь с администратором объекта.',
    ],

    'tenant_reassigned' => [
        'subject' => 'Назначение объекта обновлено',
        'greeting' => 'Здравствуйте, :name!',
        'updated' => 'Ваше назначение на объект было обновлено.',
        'previous' => '**Предыдущий объект:** :address',
        'new' => '**Новый объект:** :address',
        'assigned' => 'Вам назначен объект:',
        'property' => '**Объект:** :address',
        'property_type' => '**Тип объекта:** :type',
        'view_dashboard' => 'Открыть кабинет',
        'info' => 'Теперь вы можете просматривать сведения об услугах для этого объекта.',
        'support' => 'Если у вас есть вопросы, свяжитесь с администратором объекта.',
    ],

    'meter_reading_submitted' => [
        'subject' => 'Отправлены новые показания счетчика',
        'greeting' => 'Здравствуйте, :name!',
        'submitted_by' => 'Новые показания отправил **:tenant**.',
        'details' => '**Детали показаний:**',
        'property' => 'Объект: :address',
        'meter_type' => 'Тип счетчика: :type',
        'serial' => 'Серийный номер: :serial',
        'reading_date' => 'Дата показаний: :date',
        'reading_value' => 'Значение: :value',
        'zone' => 'Зона: :zone',
        'consumption' => 'Потребление: :consumption',
        'view' => 'Посмотреть показания',
        'manage_hint' => 'Вы можете просматривать и управлять показаниями в своем кабинете.',
    ],

    'overdue_invoice' => [
        'subject' => 'Счет #:id просрочен',
        'greeting' => 'Здравствуйте, :name,',
        'overdue' => 'Счет #:id просрочен.',
        'amount' => 'Итоговая сумма: :amount',
        'due_date' => 'Срок оплаты: :date',
        'pay_notice' => 'Пожалуйста, оплатите этот счет как можно скорее, чтобы избежать проблем с обслуживанием.',
        'action' => 'Посмотреть счет',
        'ignore' => 'Если вы уже оплатили, можете игнорировать это сообщение.',
    ],

    'profile' => [
        'updated' => 'Профиль успешно обновлен.',
        'password_updated' => 'Пароль успешно обновлен.',
    ],
];
