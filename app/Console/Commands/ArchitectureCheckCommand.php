<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Architecture\ArchitectureChecklist;
use Illuminate\Console\Command;

final class ArchitectureCheckCommand extends Command
{
    protected $signature = 'architecture:check {--json : Print machine-readable check output}';

    protected $description = 'Run Tenanto architecture boundary, documentation, and module contract checks.';

    public function handle(ArchitectureChecklist $architectureChecklist): int
    {
        $report = $architectureChecklist->assess();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return $report['ready'] ? self::SUCCESS : self::FAILURE;
        }

        $this->components->info('Architecture Check');

        foreach ($report['checks'] as $check) {
            $this->components->twoColumnDetail(
                $check['label'],
                strtoupper($check['status']).' - '.$check['summary'],
            );

            foreach ($check['details'] as $detail) {
                $this->line('  - '.$detail);
            }
        }

        $this->newLine();
        $this->line('Result: '.($report['ready'] ? 'PASSED' : 'FAILED'));

        return $report['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
