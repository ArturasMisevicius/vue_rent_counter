# Основной сценарий показаний и счетов

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/FEATURES.md`, and this file before changing billing, readings, invoices, payment proof, reminders, service configuration, tenant portal reading forms, or billing-review behavior.

Обновлено: 2026-06-15. Этот документ описывает текущий invoice-driven workflow для tenant-показаний, проверки админом/менеджером и финализации счета.

## Коротко

Tenant не вводит показания "в свободном режиме". Показания принимаются только по открытому черновику счета `reading_request`, который относится к конкретному organization, property, tenant и billing period. Новые billing periods и monthly draft invoices теперь может создавать автоматический schedule через настройки organization.

Главный пользовательский принцип: tenant видит запрос как задачу "ввести показания для текущего счета", а админ/менеджер видит это как очередь проверки перед финализацией счета.

## Шаг 1 — BillingPeriod

Billing flow начинается не со свободного invoice и не со свободного tenant reading, а с `BillingPeriod`, например `January 2026`, `February 2026` или `March 2026`.

`BillingPeriod` хранит:

- organization;
- period name;
- `starts_at` и `ends_at`;
- `reading_submission_deadline`;
- `invoice_generation_date`;
- `payment_due_date`.

Tenant, property, meters, tariffs и services не дублируются в самом периоде. Система определяет их на момент открытия цикла через активные property assignments, активные meters и активные service configurations/tariffs на даты периода. Для проверки перед запуском используется snapshot периода: он показывает, какие tenants/properties/meters/services/tariffs попадут в reading request invoices.

Админ или менеджер может создать период заранее в Billing Periods, а затем открыть для него `Open Reading Cycle` или `Generate Draft Invoices`. Команда/действие автоматической генерации также создает или обновляет `BillingPeriod` для выбранных дат, чтобы все draft invoices ссылались на один контролируемый период.

## Automatic Monthly Draft Invoices

Каждая organization настраивает расписание в Settings -> Billing:

- `auto_generation_enabled`;
- `billing_frequency`;
- `invoice_generation_day`;
- `reading_deadline_day`;
- `payment_due_days`;
- `send_created_notification`;
- `send_reminders`;
- `reminder_days_before_deadline`;
- `timezone`;
- `default_currency`.

Команда `billing:generate-draft-invoices` запускается ежедневно в 08:15. Без `--force` она обрабатывает только active organizations, у которых включена автоматическая генерация и локальный день совпадает с `invoice_generation_day`. Команда выбирает прошлый billing period для monthly schedule, создает или обновляет `BillingPeriod`, затем вызывает общий `GenerateDraftInvoicesForBillingPeriod`.

Гарантии генерации:

- scheduler idempotent: повторный запуск не создает второй active invoice для того же tenant/property/billing period;
- inactive tenants и inactive property assignments skipped;
- properties без active billable services skipped с warning;
- missing tariff или другая blocking service configuration error создает draft invoice с `approval_status = configuration_error`, пишет log item и не отправляет tenant notification;
- metered invoices получают `approval_status = waiting_for_readings` и notification tenant-у;
- fixed-only invoices получают `approval_status = ready_for_review` и ждут admin review без запроса показаний;
- каждый manual или automatic run пишет `BillingGenerationLog` и `BillingGenerationLogItem`.

Manual run находится в Billing -> Periods: `Preview Draft Invoices` делает dry-run без записи в БД, `Generate Draft Invoices` выполняет ту же action/service, что и scheduler. Результаты и ошибки смотрите в Billing -> Generation Logs.

## Главный поток

1. Scheduler или admin/manager запускает automatic monthly draft generation через `billing:generate-draft-invoices` или Billing Periods manual action. Legacy action `Open Reading Cycle` остается для совместимости.
2. `GenerateDraftInvoicesForBillingPeriod` создает или обновляет `BillingPeriod` и находит active tenant/property assignments с active services и meters.
3. Для каждого подходящего назначения `InvoiceService::createAutomaticBillingPeriodDraft()` создает пустой invoice:
   - `status = draft`;
   - `automation_level = reading_request`;
   - `approval_status = waiting_for_readings`;
   - `total_amount = 0`;
   - `approval_metadata` хранит period, deadline, linked meters, expected services и required inputs.
4. Tenant получает `InvoiceReadingRequestNotification` со ссылкой на форму показаний с `invoice=<id>`.
5. Tenant открывает `Readings` только в контексте этого invoice request. `SubmitTenantReadingAction` дополнительно проверяет backend-уровнем, что invoice request открыт и принадлежит этому tenant/property/organization.
6. После отправки `CompleteReadingRequestInvoiceAction` переводит invoice в `approval_status = readings_submitted`, сохраняет submitted reading ids и уведомляет billing reviewers.
7. Админ или менеджер открывает `Billing Review Center`.
8. Reviewer подтверждает, отклоняет, исправляет показания или запрашивает повторную отправку. `RequestReadingResubmission` возвращает invoice в `waiting_for_readings` и сохраняет tenant-visible comment.
9. Когда blocking errors устранены, reviewer пересчитывает invoice из подтвержденных readings. Invoice остается `draft`, но получает `approval_status = ready_for_review`.
10. Reviewer финализирует invoice. После финализации счет становится обычным tenant-visible invoice для просмотра, PDF/download и дальнейшей оплаты/отправки.

## Кто что делает

| Роль | Действия |
| --- | --- |
| Admin | Открывает цикл, настраивает услуги, проверяет показания, пересчитывает и финализирует счета, управляет оплатами и напоминаниями. |
| Manager | Делает те же операции только если активная manager membership и permission preset дают доступ к readings/invoices/payments. |
| Tenant | Видит уведомление, открывает конкретный request invoice, вводит требуемые показания, видит отправленный статус и финальный счет. |
| Superadmin | Может инспектировать/поддерживать платформу, но не должен становиться обычным tenant submitter. |

## Статусы

| Шаг | Invoice status | Approval status | Что значит |
| --- | --- | --- | --- |
| Открыт запрос показаний | `draft` | `waiting_for_readings` | Tenant должен ввести показания по ссылке из уведомления. |
| Tenant отправил показания | `draft` | `readings_submitted` | Показания ждут проверки billing reviewer. |
| Запрошена повторная отправка | `draft` | `waiting_for_readings` | Tenant должен заменить ошибочное показание. |
| Расчет подготовлен | `draft` | `ready_for_review` | Строки счета посчитаны, reviewer может финализировать. |
| Нет blocking errors и только fixed services | `draft` | `ready_for_review` | Tenant notification не нужна; reviewer может проверить fixed draft. |
| Ошибка конфигурации | `draft` | `configuration_error` | Требуется исправить service configuration/tariff; tenant notification не отправляется. |
| Счет финализирован | `finalized` | `approved` | Tenant видит финальный счет и может скачать PDF. |

## Запреты и гарантии

- Tenant может отправить показания только для своего organization/property/tenant workspace.
- Tenant action требует открытый `reading_request` invoice; прямой вызов без invoice rejected.
- Повторное tenant-показание для того же счетчика в рамках invoice period блокируется.
- Закрытый, чужой или уже обработанный invoice request не принимает новые показания.
- Admin/manager review scoped to current organization.
- Manager access depends on billing, invoices or meter readings edit permission.
- Invoice нельзя финализировать с blocking errors: нет показания, нет предыдущего подтвержденного показания, показание rejected/pending, нет тарифа или совместимого счетчика.
- Повторная отправка после `readings_submitted` должна проходить через `RequestReadingResubmission`, а не через создание нового свободного показания tenant-ом.
- Finalized invoice остается защищенным от произвольной правки; изменения должны идти через явные billing actions и audit trail.

## Где смотреть код

- Automatic generation action: `app/Filament/Actions/Admin/Billing/GenerateDraftInvoicesForBillingPeriod.php`
- Generation logs UI: `app/Filament/Resources/BillingGenerationLogs`
- Billing settings UI: `app/Filament/Pages/BillingSettings.php`
- Opening cycle: `app/Filament/Actions/Admin/Invoices/OpenReadingInvoiceCycleAction.php`
- Automatic generation command: `app/Console/Commands/GenerateDraftInvoicesCommand.php`
- Console command: `app/Console/Commands/OpenReadingInvoiceCycleCommand.php`
- Empty request invoice: `app/Services/Billing/InvoiceService.php`
- Tenant submission: `app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Request completion: `app/Filament/Actions/Tenant/Readings/CompleteReadingRequestInvoiceAction.php`
- Review center: `app/Filament/Pages/BillingReviewCenter.php`
- Review calculations: `app/Filament/Support/Admin/BillingReview/BuildBillingReviewForPeriod.php`
- Finalization: `app/Filament/Actions/Admin/BillingReview/ApproveInvoice.php`
- Payment follow-up: `app/Actions/Billing`
- Tenant portal read models: `app/Filament/Support/Tenant/Portal`

## Тесты, которые фиксируют поток

- `tests/Feature/Billing/OpenReadingInvoiceCycleTest.php`
- `tests/Feature/Billing/AutomaticDraftInvoiceGenerationTest.php`
- `tests/Feature/Billing/BillingPeriodWorkflowTest.php`
- `tests/Feature/Tenant/TenantSubmitReadingTest.php`
- `tests/Feature/Tenant/TenantReadingWorkflowConsistencyTest.php`
- `tests/Feature/Billing/ReadingRequestInvoiceReviewTest.php`
- `tests/Feature/Billing/BillingReviewCenterTest.php`

## Операционные команды

```bash
php artisan billing:generate-draft-invoices
php artisan billing:generate-draft-invoices --dry-run --organization=1
php artisan billing:generate-draft-invoices --force --period=2026-05
php artisan billing:open-reading-invoice-cycle
php artisan billing:mark-overdue-invoices
php artisan billing:send-reading-reminders
php artisan billing:send-payment-reminders
```

Перед запуском на реальных данных проверьте:

```bash
php artisan migrate:status
php artisan queue:work --once
```

Если очередь в production работает не через `database`, проверьте фактический queue driver и worker отдельно.
