<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\GenerateAdminReportExportJob;
use App\Models\User;

final class ScheduledExportService
{
    /**
     * @param  array<int, array{label: string, value: string}>  $summary
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function scheduleExport(
        string $filename,
        string $format,
        string $title,
        array $summary,
        array $columns,
        array $rows,
        string $emptyState,
        ?User $requestedBy = null,
    ): void {
        GenerateAdminReportExportJob::dispatch(
            $filename,
            $format,
            $title,
            $summary,
            $columns,
            $rows,
            $emptyState,
            $requestedBy?->id,
        );
    }
}
