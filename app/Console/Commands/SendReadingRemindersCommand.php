<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Filament\Actions\Notifications\SendReadingReminders;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class SendReadingRemindersCommand extends Command
{
    protected $signature = 'billing:send-reading-reminders
        {--organization=* : Limit to one or more organization IDs}
        {--date= : Local reminder date in YYYY-MM-DD format. Defaults to today in each organization timezone}';

    protected $description = 'Send tenant reading reminders based on organization billing settings.';

    public function handle(SendReadingReminders $sendReadingReminders): int
    {
        try {
            $organizationIds = $this->organizationIds();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $totals = [
            'organizations' => 0,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        Organization::query()
            ->select(['id', 'name', 'status'])
            ->active()
            ->with(['settings'])
            ->when(
                $organizationIds !== [],
                fn ($query) => $query->whereIn('id', $organizationIds),
            )
            ->chunkById(50, function ($organizations) use ($sendReadingReminders, &$totals): void {
                foreach ($organizations as $organization) {
                    $totals['organizations']++;

                    try {
                        $settings = $this->settingsFor($organization);
                        $schedule = $settings->billingSchedule();

                        if (! (bool) ($schedule['send_reminders'] ?? true)) {
                            $totals['skipped']++;
                            $this->components->twoColumnDetail((string) $organization->name, 'skipped: reminders disabled');

                            continue;
                        }

                        $milestones = $this->reminderMilestones($schedule['reminder_days_before_deadline'] ?? []);

                        if ($milestones === []) {
                            $totals['skipped']++;
                            $this->components->twoColumnDetail((string) $organization->name, 'skipped: no reminder milestones');

                            continue;
                        }

                        $sent = $sendReadingReminders->handle(
                            organization: $organization,
                            daysBeforeDeadline: $milestones,
                            asOf: $this->localDate((string) ($schedule['timezone'] ?? 'UTC')),
                        );
                    } catch (Throwable $exception) {
                        $totals['failed']++;
                        $this->components->twoColumnDetail((string) $organization->name, 'failed: '.$exception->getMessage());

                        continue;
                    }

                    $totals['sent'] += $sent;
                    $this->components->twoColumnDetail((string) $organization->name, "sent: {$sent}");
                }
            });

        $this->components->info(sprintf(
            'Done. Organizations: %d, sent: %d, skipped: %d, failed: %d.',
            $totals['organizations'],
            $totals['sent'],
            $totals['skipped'],
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
     * @return list<int>
     */
    private function reminderMilestones(mixed $milestones): array
    {
        if (! is_array($milestones)) {
            return [];
        }

        return collect($milestones)
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => max(0, min(31, (int) $value)))
            ->unique()
            ->values()
            ->all();
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
