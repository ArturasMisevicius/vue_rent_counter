<?php
echo "<h1>🚀 PHP работает!</h1>";
echo "<p><strong>Версия PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Текущая директория:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Время:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Проверка Laravel
if (file_exists('../bootstrap/app.php')) {
    echo "<p style='color: green;'>✅ Laravel файлы найдены</p>";
} else {
    echo "<p style='color: red;'>❌ Laravel файлы не найдены</p>";
}

// Проверка .env
if (file_exists('../.env')) {
    echo "<p style='color: green;'>✅ .env файл найден</p>";
} else {
    echo "<p style='color: red;'>❌ .env файл не найден</p>";
}

// Проверка базы данных
if (file_exists('../database/database.sqlite')) {
    echo "<p style='color: green;'>✅ База данных найдена</p>";
    $size = filesize('../database/database.sqlite');
    echo "<p><strong>Размер БД:</strong> " . number_format($size / 1024, 2) . " KB</p>";
} else {
    echo "<p style='color: red;'>❌ База данных не найдена</p>";
}

// Проверка прав доступа
if (is_writable('../storage')) {
    echo "<p style='color: green;'>✅ Папка storage доступна для записи</p>";
} else {
    echo "<p style='color: red;'>❌ Папка storage недоступна для записи</p>";
}

if (is_writable('../bootstrap/cache')) {
    echo "<p style='color: green;'>✅ Папка bootstrap/cache доступна для записи</p>";
} else {
    echo "<p style='color: red;'>❌ Папка bootstrap/cache недоступна для записи</p>";
}

// Проверка расширений PHP
$required_extensions = ['pdo', 'pdo_sqlite', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json'];
echo "<h2>📋 Расширения PHP:</h2>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ {$ext}</p>";
    } else {
        echo "<p style='color: red;'>❌ {$ext}</p>";
    }
}

// Попытка подключения к Laravel
echo "<h2>🔧 Тест Laravel:</h2>";
try {
    require_once '../vendor/autoload.php';
    $app = require_once '../bootstrap/app.php';
    echo "<p style='color: green;'>✅ Laravel загружен успешно</p>";
    
    // Проверка конфигурации
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    echo "<p style='color: green;'>✅ Kernel создан успешно</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка Laravel: " . $e->getMessage() . "</p>";
}
?>