<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ExportService;
use App\Services\PdfReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateAdminReportExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, array{label: string, value: string}>  $summary
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public string $filename,
        public string $format,
        public string $title,
        public array $summary,
        public array $columns,
        public array $rows,
        public string $emptyState,
        public ?int $requestedByUserId = null,
    ) {}

    public function handle(ExportService $exportService, PdfReportService $pdfReportService): void
    {
        $contents = match ($this->format) {
            'csv' => $exportService->renderCsv(
                $this->title,
                $this->summary,
                $this->columns,
                $this->rows,
            ),
            'pdf' => $pdfReportService->renderPdf(
                $this->title,
                $this->summary,
                $this->columns,
                $this->rows,
                $this->emptyState,
            ),
            default => throw new \InvalidArgumentException("Unsupported export format [{$this->format}]."),
        };

        Storage::disk('local')->put('report-exports/'.$this->filename, $contents);
    }
}
