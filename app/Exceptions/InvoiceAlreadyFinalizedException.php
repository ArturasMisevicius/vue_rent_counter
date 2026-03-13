<?php

namespace App\Exceptions;

/**
 * Exception thrown when attempting to modify a finalized invoice.
 */
class InvoiceAlreadyFinalizedException extends BillingException
{
    public function __construct(int $invoiceId)
    {
        parent::__construct("Invoice #{$invoiceId} is already finalized and cannot be modified.");
    }
}
