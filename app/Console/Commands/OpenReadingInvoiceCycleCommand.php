<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class OpenReadingInvoiceCycleCommand extends Command
{
    protected $signature = 'billing:open-reading-invoice-cycle
        {--organization=* : Limit to one or more organization IDs}
        {--period= : Billing month in YYYY-MM format. Defaults to the previous month}
        {--due-date= : Reading submission deadline. Defaults to period end plus 14 days}
        {--payment-due-date= : Payment due date after invoice review. Defaults to reading deadline plus 14 days}';

    protected $description = 'Open empty draft invoices and notify tenants to submit meter readings.';

    public function handle(OpenReadingInvoiceCycleAction $openReadingInvoiceCycle): int
    {
        try {
            [$periodStart, $periodEnd] = $this->resolveBillingPeriod();
            $dueDate = $this->resolveDueDate($periodEnd);
            $paymentDueDate = $this->resolvePaymentDueDate($dueDate);
            $organizationIds = $this->organizationIds();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $totals = [
            'organizations' => 0,
            'created' => 0,
            'skipped' => 0,
            'notified' => 0,
            'failed' => 0,
        ];

        $this->components->info(sprintf(
            'Opening reading invoice cycle for %s to %s.',
            $periodStart->toDateString(),
            $periodEnd->toDateString(),
        ));

        Organization::query()
            ->select(['id', 'name', 'status'])
            ->active()
            ->when(
                $organizationIds !== [],
                fn ($query) => $query->whereIn('id', $organizationIds),
            )
            ->chunkById(50, function ($organizations) use (
                $openReadingInvoiceCycle,
                $periodStart,
                $periodEnd,
                $dueDate,
                $paymentDueDate,
                &$totals,
            ): void {
                foreach ($organizations as $organization) {
                    $totals['organizations']++;

                    try {
                        $result = $openReadingInvoiceCycle->handle($organization, [
                            'billing_period_start' => $periodStart->toDateString(),
                            'billing_period_end' => $periodEnd->toDateString(),
                            'due_date' => $dueDate,
                            'payment_due_date' => $paymentDueDate,
                        ]);
                    } catch (Throwable $exception) {
                        $totals['failed']++;
                        $this->components->twoColumnDetail(
                            (string) $organization->name,
                            'failed: '.$exception->getMessage(),
                        );

                        continue;
                    }

                    $created = $result['created']->count();
                    $skipped = count($result['skipped']);
                    $notified = $result['notified'];

                    $totals['created'] += $created;
                    $totals['skipped'] += $skipped;
                    $totals['notified'] += $notified;

                    $this->components->twoColumnDetail(
                        (string) $organization->name,
                        "created: {$created}, skipped: {$skipped}, notified: {$notified}",
                    );
                }
            });

        $this->newLine();
        $this->components->info(sprintf(
            'Done. Organizations: %d, created: %d, skipped: %d, notified: %d, failed: %d.',
            $totals['organizations'],
            $totals['created'],
            $totals['skipped'],
            $totals['notified'],
            $totals['failed'],
        ));

        return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveBillingPeriod(): array
    {
        $period = $this->option('period');

        if (blank($period)) {
            $month = CarbonImmutable::now()->subMonthNoOverflow();
        } else {
            if (! is_string($period) || preg_match('/^\d{4}-\d{2}$/', $period) !== 1) {
                throw new InvalidArgumentException('The --period option must use YYYY-MM format.');
            }

            $month = CarbonImmutable::createFromFormat('!Y-m', $period);

            if (! $month instanceof CarbonImmutable) {
                throw new InvalidArgumentException('The --period option must use a valid YYYY-MM month.');
            }
        }

        return [
            $month->startOfMonth(),
            $month->endOfMonth(),
        ];
    }

    private function resolveDueDate(CarbonImmutable $periodEnd): string
    {
        $dueDate = $this->option('due-date');

        if (blank($dueDate)) {
            return $periodEnd->addDays(14)->toDateString();
        }

        if (! is_string($dueDate)) {
            throw new InvalidArgumentException('The --due-date option must be a date string.');
        }

        try {
            $resolvedDueDate = CarbonImmutable::parse($dueDate)->startOfDay();
        } catch (Throwable) {
            throw new InvalidArgumentException('The --due-date option must be a valid date.');
        }

        if ($resolvedDueDate->lessThan($periodEnd->startOfDay())) {
            throw new InvalidArgumentException('The --due-date option must be on or after the billing period end.');
        }

        return $resolvedDueDate->toDateString();
    }

    private function resolvePaymentDueDate(string $readingSubmissionDeadline): string
    {
        $paymentDueDate = $this->option('payment-due-date');
        $readingDeadline = CarbonImmutable::parse($readingSubmissionDeadline)->startOfDay();

        if (blank($paymentDueDate)) {
            return $readingDeadline->addDays(14)->toDateString();
        }

        if (! is_string($paymentDueDate)) {
            throw new InvalidArgumentException('The --payment-due-date option must be a date string.');
        }

        try {
            $resolvedPaymentDueDate = CarbonImmutable::parse($paymentDueDate)->startOfDay();
        } catch (Throwable) {
            throw new InvalidArgumentException('The --payment-due-date option must be a valid date.');
        }

        if ($resolvedPaymentDueDate->lessThan($readingDeadline)) {
            throw new InvalidArgumentException('The --payment-due-date option must be on or after the due date.');
        }

        return $resolvedPaymentDueDate->toDateString();
    }

    /**
     * @return array<int, int>
     */
    private function organizationIds(): array
    {
        $organizationIds = collect($this->option('organization'))
            ->filter(fn (mixed $value): bool => filled($value))
            ->values();

        $invalid = $organizationIds->first(fn (mixed $value): bool => ! is_numeric($value));

        if ($invalid !== null) {
            throw new InvalidArgumentException('The --organization option accepts numeric organization IDs only.');
        }

        return $organizationIds
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}
