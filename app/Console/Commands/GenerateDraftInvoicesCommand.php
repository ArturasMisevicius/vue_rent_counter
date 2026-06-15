<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Filament\Actions\Admin\Billing\GenerateDraftInvoicesForBillingPeriod;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class GenerateDraftInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-draft-invoices
        {--organization=* : Limit to one or more organization IDs}
        {--date= : Local scheduler date in YYYY-MM-DD format. Defaults to today in each organization timezone}
        {--period= : Billing month in YYYY-MM format. Overrides the schedule-derived period}
        {--dry-run : Preview candidates without creating periods, invoices, notifications, or logs}
        {--force : Run selected organizations even when automatic generation is disabled or not due today}';

    protected $description = 'Generate monthly draft invoices from organization billing schedule settings.';

    public function handle(GenerateDraftInvoicesForBillingPeriod $generateDraftInvoices): int
    {
        try {
            $organizationIds = $this->organizationIds();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $totals = [
            'organizations' => 0,
            'created' => 0,
            'skipped' => 0,
            'warnings' => 0,
            'errors' => 0,
            'notified' => 0,
            'failed' => 0,
        ];

        $this->components->info($dryRun
            ? 'Previewing automatic draft invoice generation.'
            : 'Generating automatic draft invoices.');

        Organization::query()
            ->select(['id', 'name', 'status'])
            ->active()
            ->with(['settings'])
            ->when(
                $organizationIds !== [],
                fn ($query) => $query->whereIn('id', $organizationIds),
            )
            ->when(
                ! $force,
                fn ($query) => $query->whereHas(
                    'settings',
                    fn ($settingsQuery) => $settingsQuery->where('auto_generation_enabled', true),
                ),
            )
            ->chunkById(50, function ($organizations) use ($generateDraftInvoices, $dryRun, $force, &$totals): void {
                foreach ($organizations as $organization) {
                    $totals['organizations']++;

                    try {
                        $settings = $this->settingsFor($organization);
                        $schedule = $settings->billingSchedule();
                        $localDate = $this->localDate($schedule['timezone']);

                        if (! $force && (int) $localDate->day !== (int) $schedule['invoice_generation_day']) {
                            $this->components->twoColumnDetail(
                                (string) $organization->name,
                                'skipped: generation is not due today',
                            );

                            continue;
                        }

                        $periodData = $this->periodData($localDate, $schedule);
                        $result = $generateDraftInvoices->handle(
                            organization: $organization,
                            billingPeriod: $periodData,
                            actor: null,
                            dryRun: $dryRun,
                            source: $dryRun ? 'command_dry_run' : 'scheduled',
                        );
                    } catch (Throwable $exception) {
                        $totals['failed']++;
                        $this->components->twoColumnDetail(
                            (string) $organization->name,
                            'failed: '.$exception->getMessage(),
                        );

                        continue;
                    }

                    $summary = $result['summary'];
                    $totals['created'] += (int) ($summary['created'] ?? 0);
                    $totals['skipped'] += (int) ($summary['skipped'] ?? 0);
                    $totals['warnings'] += (int) ($summary['warnings'] ?? 0);
                    $totals['errors'] += (int) ($summary['errors'] ?? 0);
                    $totals['notified'] += (int) ($summary['notified'] ?? 0);

                    $this->components->twoColumnDetail(
                        (string) $organization->name,
                        sprintf(
                            'created: %d, skipped: %d, warnings: %d, errors: %d, notified: %d',
                            (int) ($summary['created'] ?? 0),
                            (int) ($summary['skipped'] ?? 0),
                            (int) ($summary['warnings'] ?? 0),
                            (int) ($summary['errors'] ?? 0),
                            (int) ($summary['notified'] ?? 0),
                        ),
                    );
                }
            });

        $this->newLine();
        $this->components->info(sprintf(
            'Done. Organizations: %d, created: %d, skipped: %d, warnings: %d, errors: %d, notified: %d, failed: %d.',
            $totals['organizations'],
            $totals['created'],
            $totals['skipped'],
            $totals['warnings'],
            $totals['errors'],
            $totals['notified'],
            $totals['failed'],
        ));

        return $totals['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function settingsFor(Organization $organization): OrganizationSetting
    {
        if ($organization->settings instanceof OrganizationSetting) {
            return $organization->settings;
        }

        return new OrganizationSetting([
            'organization_id' => $organization->id,
            'auto_generation_enabled' => false,
            'billing_frequency' => 'monthly',
            'invoice_generation_day' => 1,
            'reading_deadline_day' => 5,
            'payment_due_days' => 14,
            'send_created_notification' => true,
            'send_reminders' => true,
            'reminder_days_before_deadline' => [3, 1],
            'timezone' => 'UTC',
            'default_currency' => 'EUR',
        ]);
    }

    private function localDate(string $timezone): CarbonImmutable
    {
        $date = $this->option('date');

        if (blank($date)) {
            return CarbonImmutable::now($timezone)->startOfDay();
        }

        if (! is_string($date)) {
            throw new InvalidArgumentException('The --date option must be a date string.');
        }

        try {
            return CarbonImmutable::parse($date, $timezone)->startOfDay();
        } catch (Throwable) {
            throw new InvalidArgumentException('The --date option must be a valid date.');
        }
    }

    /**
     * @param  array<string, mixed>  $schedule
     * @return array<string, mixed>
     */
    private function periodData(CarbonImmutable $localDate, array $schedule): array
    {
        [$periodStart, $periodEnd] = $this->periodRange($localDate, (string) $schedule['billing_frequency']);
        $readingDeadline = $this->dateForDay($localDate, (int) $schedule['reading_deadline_day']);

        if ($readingDeadline->lt($localDate)) {
            $readingDeadline = $this->dateForDay($localDate->addMonthNoOverflow(), (int) $schedule['reading_deadline_day']);
        }

        return [
            'billing_period_start' => $periodStart->toDateString(),
            'billing_period_end' => $periodEnd->toDateString(),
            'reading_submission_deadline' => $readingDeadline->toDateString(),
            'invoice_generation_date' => $localDate->toDateString(),
            'payment_due_date' => $readingDeadline
                ->addDays((int) $schedule['payment_due_days'])
                ->toDateString(),
            'default_currency' => (string) $schedule['default_currency'],
            'send_created_notification' => (bool) $schedule['send_created_notification'],
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periodRange(CarbonImmutable $localDate, string $frequency): array
    {
        $period = $this->option('period');

        if (filled($period)) {
            if (! is_string($period) || preg_match('/^\d{4}-\d{2}$/', $period) !== 1) {
                throw new InvalidArgumentException('The --period option must use YYYY-MM format.');
            }

            $month = CarbonImmutable::createFromFormat('!Y-m', $period);

            if (! $month instanceof CarbonImmutable) {
                throw new InvalidArgumentException('The --period option must use a valid YYYY-MM month.');
            }

            return [$month->startOfMonth(), $month->endOfMonth()];
        }

        return match ($frequency) {
            'quarterly' => $this->previousQuarterRange($localDate),
            'yearly' => [
                $localDate->subYearNoOverflow()->startOfYear(),
                $localDate->subYearNoOverflow()->endOfYear(),
            ],
            default => [
                $localDate->subMonthNoOverflow()->startOfMonth(),
                $localDate->subMonthNoOverflow()->endOfMonth(),
            ],
        };
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function previousQuarterRange(CarbonImmutable $localDate): array
    {
        $anchor = $localDate->subMonthsNoOverflow(3);
        $quarterStartMonth = ((int) floor(($anchor->month - 1) / 3) * 3) + 1;
        $quarterStart = $anchor->setMonth($quarterStartMonth)->startOfMonth();

        return [
            $quarterStart,
            $quarterStart->addMonthsNoOverflow(2)->endOfMonth(),
        ];
    }

    private function dateForDay(CarbonImmutable $date, int $day): CarbonImmutable
    {
        return $date->setDay(min(max(1, $day), min(28, $date->daysInMonth)))->startOfDay();
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
