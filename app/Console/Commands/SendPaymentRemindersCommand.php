<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\SendPaymentReminders;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'billing:send-payment-reminders
        {--actor-id= : User id used for reminder audit logs}
        {--as-of= : Override reminder date in YYYY-MM-DD format}';

    protected $description = 'Queue overdue invoice payment reminders when no payment is pending review.';

    public function handle(SendPaymentReminders $sendPaymentReminders): int
    {
        $actor = $this->resolveActor();

        if (! $actor instanceof User) {
            $this->components->error('No admin or superadmin user is available to send payment reminders.');

            return self::FAILURE;
        }

        $asOf = filled($this->option('as-of'))
            ? CarbonImmutable::parse((string) $this->option('as-of'))
            : null;
        $result = $sendPaymentReminders->handle($actor, $asOf);

        $this->components->info('Payment reminders processed.');
        $this->components->twoColumnDetail('Queued', (string) $result['queued']);
        $this->components->twoColumnDetail('Skipped pending review', (string) $result['skipped_pending_review']);

        return self::SUCCESS;
    }

    private function resolveActor(): ?User
    {
        if (filled($this->option('actor-id'))) {
            return User::query()
                ->select(['id', 'organization_id', 'name', 'email', 'role'])
                ->whereKey((int) $this->option('actor-id'))
                ->first();
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->where('role', 'superadmin')
            ->oldest('id')
            ->first()
            ?? User::query()
                ->select(['id', 'organization_id', 'name', 'email', 'role'])
                ->where('role', 'admin')
                ->oldest('id')
                ->first();
    }
}
