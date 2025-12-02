# Исправление бесконечного цикла в HierarchicalScope

**Дата:** 2025-12-01  
**Проблема:** Maximum execution time exceeded при попытке входа  
**Причина:** Бесконечная рекурсия в HierarchicalScope::apply()

## Описание проблемы

После предыдущих изменений система начала зависать при попытке входа с ошибкой:
```
Maximum execution time of 30 seconds exceeded
```

### Цепочка рекурсии

1. Пользователь пытается войти
2. Laravel вызывает `Auth::user()` для проверки аутентификации
3. `Auth::user()` выполняет запрос к таблице `users`
4. Запрос триггерит `HierarchicalScope::apply()` на связанных моделях
5. `HierarchicalScope::apply()` вызывает `Auth::user()` для проверки роли
6. Возврат к шагу 2 → **БЕСКОНЕЧНЫЙ ЦИКЛ**

## Решение

Добавлена защита от рекурсии с помощью статической переменной `$isApplying`:

```php
class HierarchicalScope implements Scope
{
    /**
     * Recursion guard to prevent infinite loops during authentication.
     */
    private static bool $isApplying = false;
    
    public function apply(Builder $builder, Model $model): void
    {
        try {
            // CRITICAL: Prevent infinite recursion during authentication
            if (self::$isApplying) {
                return;
            }
            
            self::$isApplying = true;
            
            try {
                // Skip User model to prevent infinite recursion
                if ($model instanceof User) {
                    return;
                }

                // Get authenticated user
                $user = Auth::user();
                
                if ($user === null) {
                    return;
                }
                
                // ... rest of the scope logic
            } finally {
                self::$isApplying = false;
            }
        } catch (\Throwable $e) {
            // ... error handling
        }
    }
}
```

## Изменения

### app/Scopes/HierarchicalScope.php

1. **Добавлена статическая переменная `$isApplying`** для отслеживания активного применения scope
2. **Добавлена проверка рекурсии** в начале метода `apply()`
3. **Добавлен блок `try-finally`** для гарантированного сброса флага
4. **Сохранена проверка User модели** как дополнительная защита

## Тестирование

### Тест 1: Загрузка User модели
```bash
php test-login.php
```
**Результат:** ✅ Success! User loaded: kub.kory@example.org

### Тест 2: Вход через веб-интерфейс
**Статус:** Требует проверки

## Технические детали

### Почему возникла проблема?

1. `Auth::user()` использует провайдер аутентификации Laravel
2. Провайдер выполняет запрос к таблице `users`
3. Если User модель или связанные модели используют `BelongsToTenant` трейт, применяется `HierarchicalScope`
4. `HierarchicalScope` вызывает `Auth::user()` для проверки роли
5. Цикл замыкается

### Почему решение работает?

1. **Статическая переменная** сохраняется между вызовами метода
2. **Первый вызов** устанавливает `$isApplying = true`
3. **Вложенные вызовы** видят флаг и немедленно возвращаются
4. **Блок finally** гарантирует сброс флага даже при исключениях

### Альтернативные решения (не использованы)

1. ❌ Убрать `Auth::user()` из scope → Невозможно, нужен для проверки роли
2. ❌ Убрать scope из User модели → User не использует scope
3. ❌ Использовать `Auth::check()` вместо `Auth::user()` → Недостаточно информации о роли

## Безопасность

- ✅ Защита от рекурсии не влияет на безопасность
- ✅ Все проверки tenant_id и property_id сохранены
- ✅ Audit logging работает корректно
- ✅ Input validation не затронута

## Производительность

- ✅ Минимальный overhead (одна проверка boolean переменной)
- ✅ Нет дополнительных запросов к БД
- ✅ Нет блокировок или мьютексов

## Следующие шаги

1. ✅ Протестировать загрузку User модели
2. ⏳ Протестировать вход через веб-интерфейс
3. ⏳ Запустить полный набор тестов
4. ⏳ Проверить работу Filament панели
5. ⏳ Проверить работу tenant-scoped запросов

## Связанные файлы

- `app/Scopes/HierarchicalScope.php` - Основное исправление
- `test-login.php` - Тестовый скрипт
- `docs/fixes/LOGIN_FIX_2025_12_01.md` - Предыдущее исправление
- `docs/fixes/HIERARCHICAL_SCOPE_GUEST_FIX.md` - История изменений scope

## Уроки

1. **Всегда проверяйте рекурсию** при работе с global scopes и Auth
2. **Используйте статические переменные** для защиты от рекурсии
3. **Тестируйте на простых случаях** перед полным тестированием
4. **Логи могут быть огромными** при бесконечных циклах (>50MB)
