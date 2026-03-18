<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdueReminderNotification as BaseInvoiceOverdueReminderNotification;

final class OverdueInvoiceReminderNotification extends BaseInvoiceOverdueReminderNotification
{
    public function __construct(Invoice $invoice)
    {
        parent::__construct($invoice);
    }
}
