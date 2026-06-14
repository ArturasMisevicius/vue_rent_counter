<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingPeriods;

use App\Models\BillingPeriod;
use App\Models\Organization;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class ResolveBillingPeriodForInvoiceCycleAction
{
    public function handle(
        Organization $organization,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        string $readingSubmissionDeadline,
        ?string $invoiceGenerationDate = null,
        ?string $paymentDueDate = null,
    ): BillingPeriod {
        $periodStart = CarbonImmutable::parse($startsAt->toDateString())->startOfDay();
        $periodEnd = CarbonImmutable::parse($endsAt->toDateString())->startOfDay();
        $readingDeadline = CarbonImmutable::parse($readingSubmissionDeadline)->startOfDay();
        $generationDate = filled($invoiceGenerationDate)
            ? CarbonImmutable::parse((string) $invoiceGenerationDate)->startOfDay()
            : CarbonImmutable::now()->startOfDay();
        $paymentDeadline = filled($paymentDueDate)
            ? CarbonImmutable::parse((string) $paymentDueDate)->startOfDay()
            : $readingDeadline->addDays(14);

        $billingPeriod = BillingPeriod::query()
            ->forOrganization($organization->id)
            ->forDateRange($periodStart->toDateString(), $periodEnd->toDateString())
            ->first();

        if (! $billingPeriod instanceof BillingPeriod) {
            $billingPeriod = new BillingPeriod([
                'organization_id' => $organization->id,
                'starts_at' => $periodStart->toDateString(),
                'ends_at' => $periodEnd->toDateString(),
            ]);
        }

        $billingPeriod->fill([
            'name' => $this->nameForPeriod($periodStart, $periodEnd),
            'reading_submission_deadline' => $readingDeadline->toDateString(),
            'invoice_generation_date' => $generationDate->toDateString(),
            'payment_due_date' => $paymentDeadline->toDateString(),
        ]);
        $billingPeriod->save();

        return $billingPeriod;
    }

    private function nameForPeriod(CarbonImmutable $startsAt, CarbonImmutable $endsAt): string
    {
        if (
            $startsAt->isSameDay($startsAt->startOfMonth())
            && $endsAt->isSameDay($startsAt->endOfMonth())
        ) {
            return $startsAt->format('F Y');
        }

        return $startsAt->toDateString().' - '.$endsAt->toDateString();
    }
}
