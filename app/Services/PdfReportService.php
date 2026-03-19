<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Support\Admin\Reports\ReportPdfExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PdfReportService
{
    public function __construct(
        private readonly ReportPdfExporter $reportPdfExporter,
    ) {}

    /**
     * @param  array<int, array{label: string, value: string}>  $summary
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function streamPdf(
        string $filename,
        string $title,
        array $summary,
        array $columns,
        array $rows,
        string $emptyState,
    ): StreamedResponse {
        $pdf = $this->reportPdfExporter->render(
            $title,
            $summary,
            $columns,
            array_map(
                fn (array $row): array => array_map(
                    fn (mixed $value): string => is_scalar($value) || $value === null ? (string) $value : '',
                    $row,
                ),
                $rows,
            ),
            $emptyState,
        );

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
