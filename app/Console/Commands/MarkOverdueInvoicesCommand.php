<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\MarkOverdueInvoices;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'billing:mark-overdue-invoices
        {--as-of= : Date used for overdue evaluation. Defaults to today.}';

    protected $description = 'Mark invoices overdue when the due date has passed and a confirmed balance remains.';

    public function handle(MarkOverdueInvoices $markOverdueInvoices): int
    {
        try {
            $asOf = $this->resolveAsOfDate();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $marked = $markOverdueInvoices->handle($asOf);

        $this->components->info("Marked {$marked} invoice(s) overdue as of {$asOf->toDateString()}.");

        return self::SUCCESS;
    }

    private function resolveAsOfDate(): CarbonImmutable
    {
        $asOf = $this->option('as-of');

        if (blank($asOf)) {
            return CarbonImmutable::today();
        }

        if (! is_string($asOf)) {
            throw new InvalidArgumentException('The --as-of option must be a date string.');
        }

        try {
            return CarbonImmutable::parse($asOf)->startOfDay();
        } catch (Throwable) {
            throw new InvalidArgumentException('The --as-of option must be a valid date.');
        }
    }
}
