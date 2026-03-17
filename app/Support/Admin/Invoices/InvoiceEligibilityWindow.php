<?php

namespace App\Support\Admin\Invoices;

use App\Models\PropertyAssignment;
use Carbon\CarbonInterface;

class InvoiceEligibilityWindow
{
    public function allows(
        PropertyAssignment $assignment,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
    ): bool {
        if ($assignment->assigned_at->gt($billingPeriodEnd)) {
            return false;
        }

        if ($assignment->unassigned_at === null) {
            return true;
        }

        return $billingPeriodStart->lte($assignment->unassigned_at);
    }
}
