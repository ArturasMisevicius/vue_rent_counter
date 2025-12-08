# AUDITOR CHECKLIST - Vilnius Utilities Billing Platform

**Для:** Senior Architect (External Audit)  
**Дата:** 8 декабря 2024

---

## 🎯 QUICK START

### Запуск проекта
```bash
# 1. Clone & Setup
git clone [repository]
cd vilnius-utilities-billing
composer install
cp .env.example .env
php artisan key:generate

# 2. Database
php artisan migrate
php artisan test:setup --fresh

# 3. Run Tests
php -d memory_limit=512M artisan test

# 4. Start Server
php artisan serve
```

### Доступ к админ-панели
```
URL: http://localhost:8000/admin
Superadmin: superadmin@example.com / password
Admin: admin@example.com / password
Manager: manager@example.com / password
Tenant: tenant@example.com / password
```

---

## ✅ VERIFICATION CHECKLIST

### 1. Architecture Review

#### Service Layer Pattern
- [ ] Проверить `app/Services/BillingService.php`
- [ ] Проверить `app/Services/TariffResolver.php`
- [ ] Проверить `app/Services/GyvatukasCalculator.php`
- [ ] Проверить `app/Services/InputSanitizer.php`

**Вопросы:**
- Правильно ли разделена бизнес-логика?
- Есть ли дублирование кода?
- Соблюдается ли Single Responsibility Principle?

#### Domain Models
- [ ] Проверить `app/Models/Invoice.php`
- [ ] Проверить `app/Models/Meter.php`
- [ ] Проверить `app/Models/Property.php`
- [ ] Проверить `app/Models/User.php`

**Вопросы:**
- Правильно ли настроены relationships?
- Используются ли type hints?
- Есть ли protected $fillable/$guarded?

#### Multi-Tenancy
- [ ] Проверить `app/Traits/BelongsToTenant.php`
- [ ] Проверить `app/Scopes/TenantScope.php`
- [ ] Проверить `app/Services/TenantContext.php`

**Вопросы:**
- Гарантирована ли изоляция данных?
- Нет ли способов обойти TenantScope?
- Правильно ли работает для всех ролей?

### 2. Security Audit

#### Input Sanitization
```bash
# Проверить тесты
php artisan test --filter=InputSanitizerTest
```

**Проверить:**
- [ ] XSS protection в `sanitizeText()`
- [ ] SQL injection prevention в `sanitizeIdentifier()`
- [ ] Path traversal prevention
- [ ] Numeric overflow handling

**Тест-кейсы:**
```php
// XSS
$input = '<script>alert("XSS")</script>';
$sanitized = InputSanitizer::sanitizeText($input);
// Ожидается: пустая строка или безопасный текст

// SQL Injection
$input = "'; DROP TABLE users; --";
$sanitized = InputSanitizer::sanitizeIdentifier($input);
// Ожидается: exception или sanitized string

// Path Traversal
$input = "../../../etc/passwd";
$sanitized = InputSanitizer::sanitizeIdentifier($input);
// Ожидается: exception (double dots blocked)
```

#### Authorization Policies
```bash
# Проверить policy тесты
php artisan test --filter=PolicyTest
```

**Проверить:**
- [ ] InvoicePolicy - все методы
- [ ] MeterPolicy - tenant isolation
- [ ] PropertyPolicy - cross-tenant protection
- [ ] UserPolicy - self-modification prevention

**Тест-кейсы:**
```php
// Tenant не может видеть чужие invoices
$tenant1 = User::factory()->tenant()->create();
$tenant2 = User::factory()->tenant()->create();
$invoice = Invoice::factory()->for($tenant2)->create();

$this->actingAs($tenant1)
    ->get("/invoices/{$invoice->id}")
    ->assertForbidden();
```

#### Tenant Isolation
```bash
# Проверить multi-tenancy тесты
php artisan test --filter=MultiTenancy
```

**Проверить:**
- [ ] TenantScope применяется автоматически
- [ ] Superadmin может видеть все
- [ ] Admin видит только свой tenant
- [ ] Tenant видит только свои данные

### 3. Performance Review

#### N+1 Queries
```bash
# Проверить performance тесты
php artisan test --filter=PerformanceTest
```

**Проверить:**
- [ ] `AccountManagementServicePerformanceTest`
- [ ] Eager loading в Filament Resources
- [ ] Query count в критических операциях

**Benchmark:**
```php
// Create admin account
✓ Query count: < 10 queries
✓ Execution time: < 100ms

// Reassign tenant
✓ No N+1 queries
✓ Execution time: < 50ms
```

#### Database Indexes
```sql
-- Проверить наличие индексов
SHOW INDEX FROM invoices;
SHOW INDEX FROM meter_readings;
SHOW INDEX FROM properties;

-- Ожидаемые индексы:
-- invoices: tenant_id, status, billing_period_start
-- meter_readings: meter_id, reading_date, tenant_id
-- properties: building_id, tenant_id
```

### 4. Code Quality

#### Static Analysis
```bash
# PHPStan
./vendor/bin/phpstan analyse --level=8

# Laravel Pint
./vendor/bin/pint --test
```

**Ожидаемый результат:**
- PHPStan: 0 errors (или минимальное количество)
- Pint: No style violations

#### Test Coverage
```bash
# Запустить с coverage
php artisan test --coverage --min=70
```

**Проверить:**
- [ ] Unit tests: > 80%
- [ ] Feature tests: > 70%
- [ ] Overall: > 70%

### 5. Billing Logic Verification

#### Invoice Generation
```bash
# Тест генерации инвойса
php artisan test --filter=InvoiceGenerationTest
```

**Проверить:**
- [ ] Правильный расчет потребления
- [ ] Применение тарифов
- [ ] Снапшот тарифов в invoice_items
- [ ] Расчет Gyvatukas
- [ ] Обработка multi-zone meters

**Ручная проверка:**
```php
// В tinker
$property = Property::first();
$invoice = app(BillingService::class)->generateInvoice(
    $property,
    now()->startOfMonth(),
    now()->endOfMonth()
);

// Проверить:
// 1. invoice->items содержит все meters
// 2. Каждый item имеет rate_snapshot
// 3. Суммы рассчитаны правильно
// 4. Gyvatukas добавлен для hot water
```

#### Tariff Resolution
```bash
# Тест выбора тарифов
php artisan test --filter=TariffResolverTest
```

**Проверить:**
- [ ] Выбор активного тарифа по дате
- [ ] Flat rate strategy
- [ ] Time-of-use strategy
- [ ] Weekend logic
- [ ] Fallback на default rate

### 6. Frontend Verification

#### Filament Resources
**Проверить в браузере:**
- [ ] `/admin/properties` - список, создание, редактирование
- [ ] `/admin/meters` - tenant filtering
- [ ] `/admin/invoices` - finalize action
- [ ] `/admin/meter-readings` - validation

**Проверить в коде:**
- [ ] `app/Filament/Resources/PropertyResource.php`
- [ ] `app/Filament/Resources/InvoiceResource.php`
- [ ] Navigation visibility по ролям

#### Alpine.js Integration
```bash
# Проверить сборку
npm run build

# Проверить в браузере
# Открыть DevTools → Console
# Не должно быть ошибок Alpine
```

**Проверить:**
- [ ] Alpine.js загружается корректно
- [ ] Нет конфликтов версий
- [ ] Интерактивные элементы работают

---

## 🔍 CRITICAL ISSUES TO CHECK

### 1. Memory Limit Issue
**Проблема:** Тесты требуют увеличения memory_limit

**Проверка:**
```bash
# Без memory_limit
php artisan test
# Ожидается: прерывание по памяти

# С memory_limit
php -d memory_limit=512M artisan test
# Ожидается: успешное выполнение
```

**Вопрос:** Почему требуется так много памяти? Есть ли memory leaks?

### 2. PHPUnit Deprecation Warnings
**Проблема:** 200+ warnings о doc-comment metadata

**Проверка:**
```bash
php artisan test 2>&1 | grep "WARN" | wc -l
# Ожидается: ~200 warnings
```

**Вопрос:** Когда планируется миграция на PHP 8 attributes?

### 3. Tenant Isolation
**Критическая проверка:**

```php
// В tinker
$admin1 = User::where('role', 'admin')->first();
$admin2 = User::where('role', 'admin')->skip(1)->first();

// Установить контекст admin1
TenantContext::set($admin1->tenant_id);

// Попытаться получить данные admin2
$properties = Property::all();

// КРИТИЧНО: Должны вернуться только properties admin1
// Если возвращаются properties admin2 - SECURITY ISSUE!
```

### 4. SQL Injection
**Критическая проверка:**

```php
// Попытка SQL injection в remote_id
$maliciousInput = "'; DROP TABLE tariffs; --";

try {
    Tariff::create([
        'name' => 'Test',
        'remote_id' => $maliciousInput,
        'provider_id' => 1,
        // ...
    ]);
} catch (\Exception $e) {
    // Ожидается: ValidationException или sanitized input
    // НЕ ожидается: SQL error или успешное создание
}
```

### 5. XSS Protection
**Критическая проверка:**

```php
// Попытка XSS в tariff name
$xssInput = '<script>alert("XSS")</script>';

$tariff = Tariff::create([
    'name' => $xssInput,
    // ...
]);

// В Blade view
echo $tariff->name;

// КРИТИЧНО: Должен вывестись sanitized text
// НЕ должен выполниться JavaScript
```

---

## 📊 PERFORMANCE BENCHMARKS

### Expected Performance
```
Operation                  | Target    | Actual
---------------------------|-----------|----------
Create Admin Account       | < 100ms   | ~35ms ✅
Reassign Tenant           | < 50ms    | ~27ms ✅
Generate Invoice          | < 500ms   | ~200ms ✅
List Properties (100)     | < 200ms   | TBD
API Response (p95)        | < 200ms   | TBD
```

### Load Testing (Recommended)
```bash
# Установить k6
brew install k6  # или другой способ

# Запустить load test
k6 run load-test.js

# Ожидаемые результаты:
# - 100 concurrent users
# - < 5% error rate
# - p95 response time < 500ms
```

---

## 🚨 RED FLAGS TO WATCH FOR

### Security
- [ ] Raw SQL queries (должны быть только Eloquent)
- [ ] `DB::raw()` без sanitization
- [ ] `{!! $variable !!}` в Blade (unescaped output)
- [ ] Отсутствие CSRF tokens
- [ ] Hardcoded credentials

### Performance
- [ ] N+1 queries в loops
- [ ] Отсутствие indexes на foreign keys
- [ ] `->get()` вместо `->paginate()`
- [ ] Отсутствие eager loading
- [ ] Синхронные тяжелые операции (PDF generation)

### Architecture
- [ ] Business logic в Controllers
- [ ] Business logic в Blade views
- [ ] Дублирование кода
- [ ] God classes (> 500 lines)
- [ ] Circular dependencies

### Code Quality
- [ ] Отсутствие type hints
- [ ] Отсутствие return types
- [ ] Magic numbers
- [ ] Commented out code
- [ ] TODO comments без tickets

---

## 📝 AUDIT REPORT TEMPLATE

```markdown
# Audit Report - Vilnius Utilities Billing Platform

## Executive Summary
- Overall Assessment: [Pass/Conditional Pass/Fail]
- Critical Issues: [Number]
- Major Issues: [Number]
- Minor Issues: [Number]

## Detailed Findings

### Critical Issues
1. [Issue description]
   - Severity: Critical
   - Impact: [Description]
   - Recommendation: [Action]

### Major Issues
1. [Issue description]
   - Severity: Major
   - Impact: [Description]
   - Recommendation: [Action]

### Minor Issues
1. [Issue description]
   - Severity: Minor
   - Impact: [Description]
   - Recommendation: [Action]

## Positive Findings
- [What was done well]

## Recommendations
1. [Priority 1 recommendation]
2. [Priority 2 recommendation]
3. [Priority 3 recommendation]

## Conclusion
[Final assessment and go/no-go recommendation]
```

---

## 📞 CONTACT

**Questions during audit:**
- Technical Lead: [Email]
- DevOps: [Email]
- Project Manager: [Email]

**Documentation:**
- Full Report: `docs/handover/FINAL_TECHNICAL_HANDOVER_REPORT.md`
- Architecture: `docs/handover/ARCHITECTURE_DIAGRAM.md`
- Executive Summary: `docs/handover/EXECUTIVE_SUMMARY.md`

