<?php

// Обходной путь для запуска Laravel на Windows
// Проблема: Laravel не может определить права на запись в bootstrap/cache

define('LARAVEL_START', microtime(true));

// Подключаем автозагрузчик
require_once __DIR__.'/vendor/autoload.php';

// Создаём приложение напрямую, минуя проверки кэша
$app = require_once __DIR__.'/bootstrap/app.php';

// Запускаем команду
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);

$kernel->terminate($input, $status);

exit($status);