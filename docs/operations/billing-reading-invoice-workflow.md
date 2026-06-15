# Основной сценарий показаний и счетов

> Обновлено: 2026-06-15. Этот документ описывает текущий invoice-driven workflow для tenant-показаний, проверки админом/менеджером и финализации счета.

## Коротко

Tenant не вводит показания "в свободном режиме". Показания принимаются только по открытому черновику счета `reading_request`, который относится к конкретному organization, property, tenant и billing period.

## Главный поток

1. Админ или менеджер с правами billing/invoices открывает цикл через `Open Reading Cycle` в списке счетов или команду `billing:open-reading-invoice-cycle`.
2. `OpenReadingInvoiceCycleAction` создает или обновляет `BillingPeriod` и находит активные tenant/property assignments с активными счетчиками.
3. Для каждого подходящего назначения `InvoiceService::createReadingRequestDraft()` создает пустой invoice:
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

## Статусы

| Шаг | Invoice status | Approval status | Что значит |
| --- | --- | --- | --- |
| Открыт запрос показаний | `draft` | `waiting_for_readings` | Tenant должен ввести показания по ссылке из уведомления. |
| Tenant отправил показания | `draft` | `readings_submitted` | Показания ждут проверки billing reviewer. |
| Запрошена повторная отправка | `draft` | `waiting_for_readings` | Tenant должен заменить ошибочное показание. |
| Расчет подготовлен | `draft` | `ready_for_review` | Строки счета посчитаны, reviewer может финализировать. |
| Счет финализирован | `finalized` | `approved` | Tenant видит финальный счет и может скачать PDF. |

## Запреты и гарантии

- Tenant может отправить показания только для своего organization/property/tenant workspace.
- Tenant action требует открытый `reading_request` invoice; прямой вызов без invoice rejected.
- Повторное tenant-показание для того же счетчика в рамках invoice period блокируется.
- Закрытый, чужой или уже обработанный invoice request не принимает новые показания.
- Admin/manager review scoped to current organization.
- Manager access depends on billing, invoices or meter readings edit permission.
- Invoice нельзя финализировать с blocking errors: нет показания, нет предыдущего подтвержденного показания, показание rejected/pending, нет тарифа или совместимого счетчика.

## Где смотреть код

- Opening cycle: `app/Filament/Actions/Admin/Invoices/OpenReadingInvoiceCycleAction.php`
- Empty request invoice: `app/Services/Billing/InvoiceService.php`
- Tenant submission: `app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- Request completion: `app/Filament/Actions/Tenant/Readings/CompleteReadingRequestInvoiceAction.php`
- Review center: `app/Filament/Pages/BillingReviewCenter.php`
- Review calculations: `app/Filament/Support/Admin/BillingReview/BuildBillingReviewForPeriod.php`
- Finalization: `app/Filament/Actions/Admin/BillingReview/ApproveInvoice.php`

## Тесты, которые фиксируют поток

- `tests/Feature/Billing/OpenReadingInvoiceCycleTest.php`
- `tests/Feature/Tenant/TenantSubmitReadingTest.php`
- `tests/Feature/Tenant/TenantReadingWorkflowConsistencyTest.php`
- `tests/Feature/Billing/ReadingRequestInvoiceReviewTest.php`
- `tests/Feature/Billing/BillingReviewCenterTest.php`
