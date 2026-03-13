# Исправление ошибки "Target class [role] does not exist"

## Проблема
Ошибка возникает из-за того, что middleware алиасы для Spatie Laravel Permission не зарегистрированы в Laravel 12.

## Решение

### 1. Добавлены middleware алиасы в `bootstrap/app.php`
```php
$middleware->alias([
    'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
    'tenant.set' => \App\Http\Middleware\SetTenantContext::class,
    // Spatie Laravel Permission middleware aliases
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
]);
```

### 2. Добавлен провайдер в `bootstrap/providers.php`
```php
// Spatie Permission Service Provider
Spatie\Permission\PermissionServiceProvider::class,
```

### 3. Созданы файлы
- `database/migrations/2025_01_06_000001_create_permission_tables.php` - миграция для таблиц разрешений
- `database/seeders/RolesAndPermissionsSeeder.php` - сидер для создания базовых ролей

## Следующие шаги

1. Запустить миграции:
```bash
php artisan migrate
```

2. Запустить сидер для создания ролей:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

3. Сгенерировать разрешения для Filament Shield:
```bash
php artisan shield:generate --all
```

## Проверка
После выполнения этих шагов middleware `role`, `permission` и `role_or_permission` должны работать корректно в роутах:

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Роуты для админов
});
```

## Конфигурация
- Модель User уже использует трейт `HasRoles`
- Конфигурация Spatie Permission находится в `config/permission.php`
- Конфигурация Filament Shield находится в `config/filament-shield.php`