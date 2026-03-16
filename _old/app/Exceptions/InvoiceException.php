<?php

namespace App\Exceptions;

use Exception;

class InvoiceException extends Exception
{
    /**
     * Create exception for already finalized invoice.
     */
    public static function alreadyFinalized(int $invoiceId): self
    {
        return new self(
            "Invoice {$invoiceId} is already finalized and cannot be modified"
        );
    }

    /**
     * Create exception for invalid status transition.
     */
    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self(
            "Cannot transition invoice status from {$from} to {$to}"
        );
    }

    /**
     * Create exception for missing invoice items.
     */
    public static function noItems(): self
    {
        return new self("Cannot finalize invoice without any items");
    }
}
