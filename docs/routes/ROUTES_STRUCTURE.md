# Структура маршрутов системы Vilnius Utilities Billing

## Обзор уровней доступа

### 1. ADMIN (Администратор)
- Полный доступ ко всем модулям системы
- Управление пользователями, тарифами, провайдерами
- Системные настройки и конфигурация

### 2. MANAGER (Менеджер)
- Управление недвижимостью и арендаторами
- Ввод показаний счетчиков
- Генерация и управление счетами
- Просмотр отчетов

### 3. TENANT (Арендатор)
- Просмотр своих счетов
- Просмотр истории потребления
- Просмотр информации о своей недвижимости

---

## Модуль 1: Аутентификация (Authentication)

### Доступно всем уровням

```
GET  /login                    - Форма входа
POST /login                    - Обработка входа
POST /logout                   - Выход из системы
GET  /register                 - Форма регистрации (только для tenant)
POST /register                 - Обработка регистрации
GET  /forgot-password          - Форма восстановления пароля
POST /forgot-password          - Отправка ссылки восстановления
GET  /reset-password/{token}   - Форма сброса пароля
POST /reset-password           - Обработка сброса пароля
```

---

## Модуль 2: Dashboard (Главная панель)

### ADMIN
```
GET /admin/dashboard           - Общая статистика системы
  - Количество пользователей по ролям
  - Количество недвижимости
  - Статистика счетов (draft/finalized/paid)
  - Последние действия в системе
```

### MANAGER
```
GET /manager/dashboard         - Панель менеджера
  - Список недвижимости под управлением
  - Счетчики требующие снятия показаний
  - Черновики счетов
  - Предстоящие задачи
```

### TENANT
```
GET /tenant/dashboard          - Панель арендатора
  - Информация о текущей аренде
  - Последний счет
  - График потребления
  - Контактная информация
```

---

## Модуль 3: Управление пользователями (Users)

### ADMIN
```
GET    /admin/users            - Список всех пользователей
GET    /admin/users/create     - Форма создания пользователя
POST   /admin/users            - Создание пользователя
GET    /admin/users/{id}       - Просмотр пользователя
GET    /admin/users/{id}/edit  - Форма редактирования
PUT    /admin/users/{id}       - Обновление пользователя
DELETE /admin/users/{id}       - Удаление пользователя
POST   /admin/users/{id}/reset-password - Сброс пароля
```

### MANAGER
```
GET /manager/profile           - Просмотр своего профиля
PUT /manager/profile           - Обновление своего профиля
```

### TENANT
```
GET /tenant/profile            - Просмотр своего профиля
PUT /tenant/profile            - Обновление своего профиля
```

---

## Модуль 4: Провайдеры услуг (Providers)

### ADMIN
```
GET    /admin/providers            - Список провайдеров
GET    /admin/providers/create     - Форма создания провайдера
POST   /admin/providers            - Создание провайдера
GET    /admin/providers/{id}       - Просмотр провайдера
GET    /admin/providers/{id}/edit  - Форма редактирования
PUT    /admin/providers/{id}       - Обновление провайдера
DELETE /admin/providers/{id}       - Удаление провайдера
```

### MANAGER
```
GET /manager/providers         - Просмотр списка провайдеров (read-only)
GET /manager/providers/{id}    - Просмотр провайдера (read-only)
```

---

## Модуль 5: Тарифы (Tariffs)

### ADMIN
```
GET    /admin/tariffs             - Список тарифов
GET    /admin/tariffs/create      - Форма создания тарифа
POST   /admin/tariffs             - Создание тарифа
GET    /admin/tariffs/{id}        - Просмотр тарифа
GET    /admin/tariffs/{id}/edit   - Форма редактирования
PUT    /admin/tariffs/{id}        - Обновление тарифа
DELETE /admin/tariffs/{id}        - Удаление тарифа
GET    /admin/tariffs/{id}/history - История изменений тарифа
POST   /admin/tariffs/{id}/duplicate - Дублирование тарифа
```

### MANAGER
```
GET /manager/tariffs           - Просмотр списка тарифов (read-only)
GET /manager/tariffs/{id}      - Просмотр тарифа (read-only)
```

---

## Модуль 6: Здания (Buildings)

### ADMIN & MANAGER
```
GET    /buildings                 - Список зданий
GET    /buildings/create          - Форма создания здания
POST   /buildings                 - Создание здания
GET    /buildings/{id}            - Просмотр здания
GET    /buildings/{id}/edit       - Форма редактирования
PUT    /buildings/{id}            - Обновление здания
DELETE /buildings/{id}            - Удаление здания
POST   /buildings/{id}/calculate-hot water circulation - Расчет hot water circulation
GET    /buildings/{id}/properties - Список квартир в здании
```

---

## Модуль 7: Недвижимость (Properties)

### ADMIN & MANAGER
```
GET    /properties                - Список недвижимости
GET    /properties/create         - Форма создания недвижимости
POST   /properties                - Создание недвижимости
GET    /properties/{id}           - Просмотр недвижимости
GET    /properties/{id}/edit      - Форма редактирования
PUT    /properties/{id}           - Обновление недвижимости
DELETE /properties/{id}           - Удаление недвижимости
GET    /properties/{id}/meters    - Список счетчиков
GET    /properties/{id}/tenants   - Список арендаторов
GET    /properties/{id}/invoices  - Список счетов
```

### TENANT
```
GET /tenant/property           - Просмотр своей недвижимости
GET /tenant/property/meters    - Просмотр счетчиков
```

---

## Модуль 8: Арендаторы (Tenants)

### ADMIN & MANAGER
```
GET    /tenants                   - Список арендаторов
GET    /tenants/create            - Форма создания арендатора
POST   /tenants                   - Создание арендатора
GET    /tenants/{id}              - Просмотр арендатора
GET    /tenants/{id}/edit         - Форма редактирования
PUT    /tenants/{id}              - Обновление арендатора
DELETE /tenants/{id}              - Удаление арендатора
GET    /tenants/{id}/invoices     - Счета арендатора
GET    /tenants/{id}/consumption  - История потребления
POST   /tenants/{id}/send-invoice - Отправка счета по email
```

---

## Модуль 9: Счетчики (Meters)

### ADMIN & MANAGER
```
GET    /meters                    - Список счетчиков
GET    /meters/create             - Форма создания счетчика
POST   /meters                    - Создание счетчика
GET    /meters/{id}               - Просмотр счетчика
GET    /meters/{id}/edit          - Форма редактирования
PUT    /meters/{id}               - Обновление счетчика
DELETE /meters/{id}               - Удаление счетчика
GET    /meters/{id}/readings      - История показаний
GET    /meters/pending-readings   - Счетчики требующие снятия показаний
```

### TENANT
```
GET /tenant/meters             - Просмотр своих счетчиков
GET /tenant/meters/{id}        - Просмотр счетчика
```

---

## Модуль 10: Показания счетчиков (Meter Readings)

### ADMIN & MANAGER
```
GET    /meter-readings            - Список показаний
GET    /meter-readings/create     - Форма ввода показаний
POST   /meter-readings            - Создание показания
GET    /meter-readings/{id}       - Просмотр показания
GET    /meter-readings/{id}/edit  - Форма редактирования
PUT    /meter-readings/{id}       - Обновление показания
DELETE /meter-readings/{id}       - Удаление показания
GET    /meter-readings/{id}/audit - Аудит изменений
POST   /meter-readings/bulk       - Массовый ввод показаний
GET    /meter-readings/export     - Экспорт показаний
```

### TENANT
```
GET /tenant/meter-readings     - Просмотр своих показаний
GET /tenant/meter-readings/{id} - Просмотр показания
```

---

## Модуль 11: Счета (Invoices)

### ADMIN & MANAGER
```
GET    /invoices                  - Список счетов
GET    /invoices/create           - Форма создания счета
POST   /invoices                  - Создание счета
GET    /invoices/{id}             - Просмотр счета
GET    /invoices/{id}/edit        - Форма редактирования (только draft)
PUT    /invoices/{id}             - Обновление счета (только draft)
DELETE /invoices/{id}             - Удаление счета (только draft)
POST   /invoices/{id}/finalize    - Финализация счета
POST   /invoices/{id}/mark-paid   - Отметить как оплаченный
GET    /invoices/{id}/pdf         - Скачать PDF
POST   /invoices/{id}/send        - Отправить по email
POST   /invoices/generate-bulk    - Массовая генерация счетов
GET    /invoices/drafts           - Черновики счетов
GET    /invoices/finalized        - Финализированные счета
GET    /invoices/paid             - Оплаченные счета
```

### TENANT
```
GET /tenant/invoices           - Список своих счетов
GET /tenant/invoices/{id}      - Просмотр счета
GET /tenant/invoices/{id}/pdf  - Скачать PDF
```

---

## Модуль 12: Позиции счетов (Invoice Items)

### ADMIN & MANAGER
```
GET    /invoices/{invoice_id}/items        - Список позиций счета
POST   /invoices/{invoice_id}/items        - Добавление позиции
GET    /invoices/{invoice_id}/items/{id}   - Просмотр позиции
PUT    /invoices/{invoice_id}/items/{id}   - Обновление позиции
DELETE /invoices/{invoice_id}/items/{id}   - Удаление позиции
```

---

## Модуль 13: Отчеты (Reports)

### ADMIN & MANAGER
```
GET /reports                       - Главная страница отчетов
GET /reports/consumption           - Отчет по потреблению
GET /reports/revenue               - Отчет по доходам
GET /reports/outstanding           - Отчет по неоплаченным счетам
GET /reports/meter-readings        - Отчет по показаниям счетчиков
GET /reports/hot water circulation             - Отчет по hot water circulation
GET /reports/tariff-comparison     - Сравнение тарифов
POST /reports/export               - Экспорт отчета
```

---

## Модуль 14: Аудит (Audit Trail)

### ADMIN
```
GET /admin/audit                   - Журнал аудита
GET /admin/audit/{id}              - Детали записи аудита
GET /admin/audit/meter-readings    - Аудит показаний счетчиков
GET /admin/audit/invoices          - Аудит счетов
GET /admin/audit/users             - Аудит действий пользователей
```

### MANAGER
```
GET /manager/audit/meter-readings  - Аудит своих показаний счетчиков
```

---

## Модуль 15: Настройки системы (System Settings)

### ADMIN
```
GET  /admin/settings               - Настройки системы
PUT  /admin/settings               - Обновление настроек
GET  /admin/settings/backup        - Настройки резервного копирования
POST /admin/settings/backup/run    - Запуск резервного копирования
GET  /admin/settings/maintenance   - Режим обслуживания
POST /admin/settings/cache/clear   - Очистка кеша
```

---

## API Endpoints (для будущей интеграции)

### ADMIN & MANAGER
```
GET    /api/properties             - Список недвижимости (JSON)
GET    /api/meters                 - Список счетчиков (JSON)
POST   /api/meter-readings         - Создание показания (JSON)
GET    /api/tariffs/active         - Активные тарифы (JSON)
POST   /api/invoices/generate      - Генерация счета (JSON)
```

---

## Middleware и защита маршрутов

### Применяемые middleware:
- `auth` - Требует аутентификации
- `role:admin` - Только для администраторов
- `role:admin,manager` - Для администраторов и менеджеров
- `role:tenant` - Только для арендаторов
- `tenant.context` - Проверка контекста tenant_id
- `invoice.editable` - Проверка возможности редактирования счета

---

## Сводная таблица доступа к модулям

| Модуль | ADMIN | MANAGER | TENANT |
|--------|-------|---------|--------|
| Аутентификация | ✓ | ✓ | ✓ |
| Dashboard | ✓ | ✓ | ✓ |
| Пользователи | CRUD | Profile | Profile |
| Провайдеры | CRUD | Read | - |
| Тарифы | CRUD | Read | - |
| Здания | CRUD | CRUD | - |
| Недвижимость | CRUD | CRUD | Read (своя) |
| Арендаторы | CRUD | CRUD | - |
| Счетчики | CRUD | CRUD | Read (свои) |
| Показания счетчиков | CRUD | CRUD | Read (свои) |
| Счета | CRUD | CRUD | Read (свои) |
| Отчеты | ✓ | ✓ | - |
| Аудит | Full | Limited | - |
| Настройки системы | ✓ | - | - |

---

## Примечания

1. Все маршруты защищены middleware `auth`
2. Маршруты с tenant-scoped данными используют `TenantScope`
3. CRUD операции включают валидацию через Form Requests
4. Все изменения критичных данных логируются в audit trail
5. PDF генерация счетов использует Laravel Dompdf
6. Email уведомления отправляются через Laravel Mail
7. Экспорт данных поддерживает форматы: CSV, Excel, PDF
