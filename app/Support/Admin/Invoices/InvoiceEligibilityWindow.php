<?php

namespace App\Support\Admin\Invoices;

use App\Models\PropertyAssignment;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class InvoiceEligibilityWindow
{
    public function allows(PropertyAssignment $assignment, CarbonInterface|string $billingPeriodStart): bool
    {
        if ($assignment->unassigned_at === null) {
            return true;
        }

        $billingStart = $billingPeriodStart instanceof CarbonInterface
            ? $billingPeriodStart->copy()->startOfDay()
            : Carbon::parse($billingPeriodStart)->startOfDay();

        return $billingStart->lessThanOrEqualTo($assignment->unassigned_at);
    }
}
