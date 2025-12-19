# Сравнительный Анализ: Промпт vs Реальный Код
**Дата:** 2025-12-18
**Контекст:** Анализ проблемы role-based visibility (Admin видит меньше чем Manager)

---

## EXECUTIVE SUMMARY

**Статус Бага:** ✅ ПОДТВЕРЖДЁН, но реализация отличается от промпта

**Ключевая Разница:**
- **Промпт ожидает:** Role hierarchy с role_id (1, 2, 3, 4) + привязка Manager к конкретным зданиям
- **Реальный код использует:** String enum роли + Admin имеет ПОЛНЫЙ доступ, Manager ограничен tenant_id

**Вердикт:** Код НЕ соответствует ожиданиям промпта, но БАГ подтверждается противоположной логикой - Admin видит ВСЁ, а не меньше.

---

## 1. СИСТЕМА РОЛЕЙ: ПРОМПТ VS КОД

### ❌ ПРОМПТ ОЖИДАЕТ (Неверно)
```php
// Role IDs
1 - Superadmin
2 - Admin (property owner)
3 - Manager (building manager)
4 - Tenant

// Hierarchy: 1 > 2 > 3 > 4
```

### ✅ РЕАЛЬНЫЙ КОД (Фактически)
```php
// File: app/Enums/UserRole.php
enum UserRole: string
{
    case SUPERADMIN = 'superadmin';  // НЕТ role_id!
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';
}
```

**🔴 КРИТИЧЕСКАЯ РАЗНИЦА #1:**
- Промпт: Использует числовые ID ролей (role_id: 1, 2, 3, 4)
- Код: Использует string enum ('superadmin', 'admin', 'manager', 'tenant')
- **Impact:** Все проверки в промпте `$user->role_id === 2` НЕ ПРИМЕНИМЫ к текущему коду

---

## 2. ОПИСАНИЕ РОЛЕЙ: ПРОМПТ VS КОД

### Промпт: Manager = Building Manager (менеджер здания)
```
Manager (role_id: 3) - building manager, sees ONLY assigned buildings
- Привязан к конкретным зданиям
- Видит ТОЛЬКО назначенные ему здания
- Более ограничен чем Admin
```

### Код: Manager = Legacy Admin (устаревшая версия Admin)
```php
// File: app/Enums/UserRole.php (Lines 31-36)
/**
 * **MANAGER** (Legacy Role):
 * - Purpose: Similar to Admin, maintained for backward compatibility
 * - Permissions: Same as Admin role
 * - Access: Limited to their own tenant_id scope
 * - Data Scope: Unique tenant_id for organization
 * - Note: New accounts should use ADMIN role; MANAGER is for existing accounts
 */
```

**🔴 КРИТИЧЕСКАЯ РАЗНИЦА #2:**
- **Промпт:** Manager = менеджер отдельных зданий (специфичная роль)
- **Код:** Manager = legacy роль, изначально РАВНАЯ Admin
- **Impact:** Концепция "manager assigned to specific buildings" НЕ СУЩЕСТВУЕТ в коде

---

## 3. BUILDINGPOLICY: ПОСТРОЧНОЕ СРАВНЕНИЕ

### 🔍 view() Method - Промпт vs Код

#### ❌ ПРОМПТ ОЖИДАЕТ:
```php
public function view(User $user, Building $building): bool
{
    // Superadmin sees all
    if ($user->role_id === 1) return true;

    // Admin sees all buildings in HIS organization
    if ($user->role_id === 2) {
        return $user->tenant_id === $building->tenant_id;  // ← TENANT SCOPE!
    }

    // Manager sees only if ASSIGNED to this building
    if ($user->role_id === 3) {
        return $building->managers()->where('user_id', $user->id)->exists();  // ← ASSIGNMENT!
    }

    return false;
}
```

#### ✅ РЕАЛЬНЫЙ КОД:
```php
// File: app/Policies/BuildingPolicy.php (Lines 32-57)
public function view(User $user, Building $building): bool
{
    // Superadmin can view any building
    if ($user->role === UserRole::SUPERADMIN) {
        return true;
    }

    // Admins can view buildings across tenants; managers remain tenant-scoped
    if ($user->role === UserRole::ADMIN) {
        return true;  // ← БАГ! NO TENANT CHECK!
    }

    if ($user->role === UserRole::MANAGER) {
        return $building->tenant_id === $user->tenant_id;  // ← ТОЛЬКО tenant_id
    }

    // Tenants can only view their property's building
    if ($user->role === UserRole::TENANT && $user->property_id) {
        $property = $user->property;
        if ($property) {
            return $property->building_id === $building->id;
        }
    }

    return false;
}
```

### 🔴 КРИТИЧЕСКИЕ РАЗЛИЧИЯ:

| Аспект | Промпт Ожидает | Реальный Код | Результат |
|--------|----------------|--------------|-----------|
| **Admin scope** | `tenant_id === building->tenant_id` | `return true` (БЕЗ ПРОВЕРКИ) | ❌ Admin видит ВСЁ |
| **Manager scope** | `managers()->exists()` (assignment) | `tenant_id === building->tenant_id` | ❌ Manager ограничен tenant |
| **Manager-Building relation** | Pivot table `building_manager` | НЕ СУЩЕСТВУЕТ | ❌ Невозможно назначить manager |
| **Access level** | Admin < Manager (баг) | Admin > Manager (ожидаемо) | ⚠️ ОБРАТНАЯ ЛОГИКА |

---

## 4. PROPERTYPOLICY: ТА ЖЕ ПРОБЛЕМА

### ✅ РЕАЛЬНЫЙ КОД:
```php
// File: app/Policies/PropertyPolicy.php (Lines 34-62)
public function view(User $user, Property $property): bool
{
    if ($user->role === UserRole::SUPERADMIN) {
        return true;
    }

    // Admins can view properties across tenants; managers remain tenant-scoped
    if ($user->role === UserRole::ADMIN) {
        return true;  // ← БАГ! NO TENANT CHECK!
    }

    if ($user->role === UserRole::MANAGER) {
        // Verify property belongs to manager's tenant_id
        return $property->tenant_id === $user->tenant_id;  // ← ОГРАНИЧЕН
    }

    // ... tenant logic
}
```

**🔴 ИДЕНТИЧНАЯ ПРОБЛЕМА:**
- Admin: `return true` (полный доступ)
- Manager: `return $property->tenant_id === $user->tenant_id` (ограничен)

---

## 5. ЗАТРОНУТЫЕ POLICIES (Grep Analysis)

### Pattern: `if ($user->role === UserRole::ADMIN) { return true; }`

**Найдено в:**
```
app\Policies\BuildingPolicy.php:   4 occurrences
app\Policies\MeterPolicy.php:       4 occurrences
app\Policies\PropertyPolicy.php:    4 occurrences
app\Policies\SubscriptionPolicy.php: 2 occurrences
---
TOTAL: 14 occurrences across 4 policies
```

**🚨 МАСШТАБ ПРОБЛЕМЫ:**
- Минимум **4 Policy files** с одинаковым багом
- **14 методов** где Admin имеет неограниченный доступ
- Manager везде ограничен `tenant_id`

---

## 6. DATABASE SCHEMA: MANAGER RELATIONSHIPS

### ❌ ПРОМПТ ОЖИДАЕТ:
```sql
-- Pivot table для назначения Manager к Buildings
CREATE TABLE building_manager (
    building_id INT,
    user_id INT,  -- manager
    assigned_at TIMESTAMP
);

-- Или поле в buildings
ALTER TABLE buildings ADD COLUMN manager_id INT;
```

### ✅ РЕАЛЬНЫЙ КОД:
```php
// File: app/Models/Building.php
class Building extends Model
{
    use BelongsToTenant;  // ← Только tenant_id!

    protected $fillable = [
        'tenant_id',   // ← ЕСТЬ
        'name',
        'address',
        'total_apartments',
        // 'manager_id' - НЕТ!
    ];

    public function properties(): HasMany { ... }
    // public function managers() - НЕТ!
}
```

**🔴 КРИТИЧЕСКАЯ РАЗНИЦА #3:**
- **Промпт:** Ожидает механизм назначения Manager к зданиям
- **Код:** НЕТ полей manager_id, НЕТ pivot table, НЕТ relationships
- **Grep результат:** `No files found` для "manager_id|managers()"
- **Impact:** Невозможно реализовать логику промпта без миграций

---

## 7. ACTUAL BUG CONFIRMATION

### Что описывает промпт:
> Admin (role_id: 2) sees FEWER resources than Manager (role_id: 3)

### Что на самом деле в коде:
> **Admin sees MORE (everything) than Manager (tenant-scoped)**

**Это ОБРАТНАЯ проблема!**

### Visualization:

#### Промпт описывает (неверно):
```
Superadmin ────────────────────────── Видит всё
  │
Admin ────────── Видит tenant_id ──── Баг: меньше чем Manager
  │
Manager ───────── Видит всё ────────── Баг: больше чем Admin
  │
Tenant ────────── Видит property ───── OK
```

#### Реальный код (фактически):
```
Superadmin ────────────────────────── Видит всё ✅
  │
Admin ─────────── Видит ВСЁ ────────── БАГ: должен быть tenant-scoped!
  │
Manager ───────── Видит tenant_id ──── OK (но должен быть building-scoped)
  │
Tenant ────────── Видит property ───── OK ✅
```

---

## 8. ROOT CAUSE ANALYSIS

### Почему Admin видит всё?

**Проблемный код появляется в нескольких местах:**

#### Пример #1: BuildingPolicy.php:40-42
```php
// Admins can view buildings across tenants; managers remain tenant-scoped
if ($user->role === UserRole::ADMIN) {
    return true;  // ← Comment says "across tenants" - это баг!
}
```

#### Пример #2: PropertyPolicy.php:42-44
```php
// Admins can view properties across tenants; managers remain tenant-scoped
if ($user->role === UserRole::ADMIN) {
    return true;  // ← Intentional? Or bug?
}
```

**🔍 АНАЛИЗ КОММЕНТАРИЕВ:**

Комментарии говорят `"Admins can view buildings across tenants"` - это означает:
- ✅ Либо это **задуманная функция** (Admin как супер-юзер внутри системы)
- ❌ Либо это **неправильная реализация** (Admin должен быть tenant-scoped)

**ВОПРОС К АРХИТЕКТУРЕ:**
- Должен ли Admin видеть **все tenant_id** (multi-tenant platform admin)?
- Или Admin должен видеть **только свой tenant_id** (organization owner)?

---

## 9. COMPARISON SUMMARY

### ✅ ЧТО СОВПАДАЕТ С ПРОМПТОМ:
1. ✅ Роли: Superadmin, Admin, Manager, Tenant существуют
2. ✅ Проблема visibility между Admin и Manager существует
3. ✅ Manager привязан к tenant_id
4. ✅ Tenant ограничен property_id

### ❌ ЧТО НЕ СОВПАДАЕТ С ПРОМПТОМ:
1. ❌ **Role system:** String enum вместо role_id (1,2,3,4)
2. ❌ **Manager concept:** Legacy role вместо building manager
3. ❌ **Manager-Building assignment:** НЕ СУЩЕСТВУЕТ (нет pivot table, нет manager_id)
4. ❌ **Admin scope:** Видит ВСЁ вместо tenant-scoped
5. ❌ **Bug direction:** ОБРАТНАЯ - Admin видит БОЛЬШЕ, не меньше
6. ❌ **Expected logic:** Промпт предполагает другую архитектуру

---

## 10. ANSWERS TO PROMPT QUESTIONS

### Q1: Is there a `building_manager` pivot table or `buildings.manager_id` field?
**❌ НЕТ**
- Grep search: `No files found`
- Building model: Нет relationship `managers()`
- Database migrations: Нет упоминаний manager_id

### Q2: How are Managers currently assigned to Buildings?
**❌ НИКАК - механизм не реализован**
- Manager ограничен только `tenant_id`
- Нет способа назначить Manager к конкретному зданию
- Manager видит ВСЕ здания в своём tenant_id (как Admin должен)

### Q3: Which specific Resources does Admin NOT see that Manager DOES see?
**🔄 ОБРАТНАЯ СИТУАЦИЯ:**
- **Admin ВИДИТ:** Всё (across all tenant_id)
- **Manager ВИДИТ:** Только свой tenant_id
- **ВЫВОД:** Admin видит БОЛЬШЕ, не меньше (обратно промпту)

### Q4: Suggested fixes for each Policy?
**Зависит от требований:**

#### Вариант A: Admin должен быть tenant-scoped (property owner)
```php
// FIX для всех Policies
if ($user->role === UserRole::ADMIN) {
    return $resource->tenant_id === $user->tenant_id;  // ← ADD CHECK
}
```

#### Вариант B: Manager должен быть building-scoped (требует миграций)
```php
// 1. Create migration: add building_user pivot table
// 2. Add Building model relationship:
public function managers(): BelongsToMany {
    return $this->belongsToMany(User::class, 'building_user')
                ->wherePivot('role', 'manager');
}

// 3. Update BuildingPolicy:
if ($user->role === UserRole::MANAGER) {
    return $building->managers()->where('user_id', $user->id)->exists();
}
```

---

## 11. AFFECTED RESOURCES (from Prompt)

### Промпт спрашивает: "Which Resources visible to Manager but NOT to Admin?"

**РЕАЛЬНОСТЬ: ОБРАТНОЕ**

Resources где Admin видит БОЛЬШЕ чем Manager:

1. **BuildingResource**
   - Admin: ВСЕ здания (all tenant_id)
   - Manager: Только tenant_id = user.tenant_id

2. **PropertyResource**
   - Admin: ВСЕ properties (all tenant_id)
   - Manager: Только tenant_id = user.tenant_id

3. **MeterResource** (предположительно та же логика)
   - Admin: Всё
   - Manager: Tenant-scoped

4. **InvoiceResource** (проверено частично)
   - Admin: Возможно всё
   - Manager: Tenant-scoped

---

## 12. RECOMMENDED FIXES

### 🎯 ВАРИАНТ 1: FIX ADMIN SCOPE (Простой, без миграций)

**Цель:** Admin = property owner (tenant-scoped)

**Файлы для изменения:**
```
app/Policies/BuildingPolicy.php
app/Policies/PropertyPolicy.php
app/Policies/MeterPolicy.php
app/Policies/SubscriptionPolicy.php
+ остальные policies с тем же паттерном
```

**Изменение:**
```php
// BEFORE (Bug)
if ($user->role === UserRole::ADMIN) {
    return true;  // ← No check
}

// AFTER (Fixed)
if ($user->role === UserRole::ADMIN) {
    return $resource->tenant_id === $user->tenant_id;  // ← Add tenant check
}
```

**Impact:**
- ✅ Admin теперь tenant-scoped
- ✅ Admin = Manager по доступу (как задумано в enum)
- ⚠️ Если есть Superadmin-like Admins, они потеряют доступ

---

### 🎯 ВАРИАНТ 2: IMPLEMENT MANAGER-BUILDING ASSIGNMENT (Сложный, с миграциями)

**Цель:** Manager = building manager (building-scoped)

**Шаг 1: Create Migration**
```bash
php artisan make:migration create_building_user_table
```

```php
Schema::create('building_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('building_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('role')->default('manager');
    $table->timestamp('assigned_at')->useCurrent();
    $table->unique(['building_id', 'user_id']);
});
```

**Шаг 2: Update Building Model**
```php
// app/Models/Building.php
public function managers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'building_user')
                ->wherePivot('role', 'manager')
                ->withTimestamps();
}
```

**Шаг 3: Update BuildingPolicy**
```php
if ($user->role === UserRole::MANAGER) {
    // Check if manager is assigned to this building
    return $building->managers()->where('user_id', $user->id)->exists();
}
```

**Impact:**
- ✅ Manager теперь building-scoped
- ✅ Можно назначать Manager к конкретным зданиям
- ⚠️ Требует UI для назначения managers
- ⚠️ Breaking change для существующих Manager users

---

### 🎯 ВАРИАНТ 3: DEPRECATE MANAGER ROLE (Радикальный)

**Цель:** Убрать Manager роль (она уже legacy)

**Логика:**
- Manager описан как "Legacy Role" в коде
- Комментарии говорят "New accounts should use ADMIN role"
- Проще иметь одну роль Admin

**Действия:**
1. Migrate все Manager users к Admin роли
2. Обновить все policies убрав Manager checks
3. Оставить только: Superadmin, Admin, Tenant

**Impact:**
- ✅ Упрощает code base
- ✅ Убирает путаницу
- ⚠️ Breaking change

---

## 13. FINAL VERDICT

### Промпт vs Реальность

| Критерий | Промпт | Код | Match? |
|----------|--------|-----|--------|
| Role system | role_id (int) | string enum | ❌ |
| Admin scope | tenant-scoped | UNRESTRICTED | ❌ |
| Manager scope | building-scoped | tenant-scoped | ❌ |
| Manager assignment | pivot table | НЕ СУЩЕСТВУЕТ | ❌ |
| Bug direction | Admin < Manager | Admin > Manager | ❌ OPPOSITE |
| Fix approach | Add checks | Add checks | ✅ |

**ВЫВОД:**
Промпт описывает **ДРУГУЮ АРХИТЕКТУРУ**, но **БАГ ПОДТВЕРЖДАЕТСЯ** в обратном направлении:
- Промпт думает: Admin видит меньше
- Реальность: Admin видит больше (всё)
- Оба варианта - БАГ, просто разные

---

## 14. NEXT STEPS

### Immediate Action Required:

1. **Определить бизнес-требование:**
   - ❓ Должен ли Admin быть tenant-scoped или global?
   - ❓ Нужна ли роль Manager или она deprecated?
   - ❓ Нужен ли building-level access control?

2. **После уточнения - выбрать Fix Variant:**
   - Вариант 1: Fix Admin scope (простой)
   - Вариант 2: Implement Manager assignments (сложный)
   - Вариант 3: Deprecate Manager (радикальный)

3. **Тестирование:**
   - Create test users для каждой роли
   - Verify доступ к Buildings, Properties, Meters
   - Check Filament Resources visibility

4. **Documentation:**
   - Обновить USER_MODEL_API.md
   - Добавить Role-based access matrix
   - Document expected behavior

---

## 15. CODE SNIPPETS FOR COMPARISON

### Промпт Expected Logic:
```php
// Prompt's expected BuildingPolicy::view()
public function view(User $user, Building $building): bool
{
    if ($user->role_id === 1) return true;  // Superadmin

    if ($user->role_id === 2) {  // Admin
        return $user->tenant_id === $building->tenant_id;
    }

    if ($user->role_id === 3) {  // Manager
        return $building->managers()->where('user_id', $user->id)->exists();
    }

    return false;
}
```

### Actual Code:
```php
// Current BuildingPolicy::view() - app/Policies/BuildingPolicy.php:32-57
public function view(User $user, Building $building): bool
{
    if ($user->role === UserRole::SUPERADMIN) {
        return true;
    }

    if ($user->role === UserRole::ADMIN) {
        return true;  // ← BUG: No tenant check!
    }

    if ($user->role === UserRole::MANAGER) {
        return $building->tenant_id === $user->tenant_id;
    }

    if ($user->role === UserRole::TENANT && $user->property_id) {
        $property = $user->property;
        if ($property) {
            return $property->building_id === $building->id;
        }
    }

    return false;
}
```

### Difference Highlight:
```diff
- if ($user->role_id === 2) {  // Prompt
+ if ($user->role === UserRole::ADMIN) {  // Code

- return $user->tenant_id === $building->tenant_id;  // Prompt expectation
+ return true;  // Code reality - BUG!

- if ($user->role_id === 3) {  // Prompt
+ if ($user->role === UserRole::MANAGER) {  // Code

- return $building->managers()->where('user_id', $user->id)->exists();  // Prompt
+ return $building->tenant_id === $user->tenant_id;  // Code - different logic
```

---

## CONCLUSION

**Промпт описывает архитектуру которой НЕТ в коде, но БАГ реальный (обратный)**

**Требуется уточнение бизнес-требований для выбора правильного Fix варианта.**

---

**Prepared by:** Claude Code AI
**Analysis Type:** Comparative Architecture Review
**Status:** Awaiting Business Requirements Clarification
