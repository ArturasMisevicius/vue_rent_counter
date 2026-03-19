<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Support\Admin\Reports\ReportPdfExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportService
{
    public function __construct(
        private readonly ReportPdfExporter $reportPdfExporter,
    ) {}

    /**
     * @param  array<int, array{label: string, value: string}>  $summary
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function streamCsv(
        string $filename,
        string $title,
        array $summary,
        array $columns,
        array $rows,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($title, $summary, $columns, $rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [$title]);

            foreach ($summary as $item) {
                fputcsv($handle, [$item['label'], $item['value']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, array_map(
                fn (array $column): string => $column['label'],
                $columns,
            ));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn (array $column): string => (string) ($row[$column['key']] ?? ''),
                    $columns,
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

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
