<?php

namespace App\Filament\Support\Admin\Reports;

class ReportPdfExporter
{
    /**
     * @param  array<int, array{label: string, value: string}>  $summary
     * @param  array<int, array{key: string, label: string}>  $columns
     * @param  array<int, array<string, string>>  $rows
     */
    public function render(string $title, array $summary, array $columns, array $rows, string $emptyState): string
    {
        $lines = [$title, ''];

        foreach ($summary as $item) {
            $lines = [
                ...$lines,
                ...$this->wrapLine($item['label'].': '.$item['value']),
            ];
        }

        $lines[] = '';

        $header = implode(' | ', array_map(fn (array $column): string => $column['label'], $columns));

        $lines = [
            ...$lines,
            ...$this->wrapLine($header),
            str_repeat('-', min(strlen($header), 110)),
        ];

        if ($rows === []) {
            $lines = [
                ...$lines,
                ...$this->wrapLine($emptyState),
            ];
        } else {
            foreach ($rows as $row) {
                $line = implode(' | ', array_map(
                    fn (array $column): string => $row[$column['key']] ?? '',
                    $columns,
                ));

                $lines = [
                    ...$lines,
                    ...$this->wrapLine($line),
                ];
            }
        }

        return $this->buildPdf($lines);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function buildPdf(array $lines): string
    {
        $pages = array_chunk($lines, 46);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $kids = [];
        $nextObjectId = 4;

        foreach ($pages as $pageLines) {
            $pageObjectId = $nextObjectId++;
            $contentObjectId = $nextObjectId++;

            $kids[] = $pageObjectId.' 0 R';

            $contentStream = $this->contentStream($pageLines);

            $objects[$pageObjectId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObjectId.' 0 R >>';
            $objects[$contentObjectId] = '<< /Length '.strlen($contentStream)." >>\nstream\n".$contentStream."\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Count '.count($kids).' /Kids ['.implode(' ', $kids).'] >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $objectId => $objectBody) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId." 0 obj\n".$objectBody."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxObjectId = max(array_keys($objects));

        $pdf .= 'xref'."\n";
        $pdf .= '0 '.($maxObjectId + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($objectId = 1; $objectId <= $maxObjectId; $objectId++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId]);
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size '.($maxObjectId + 1).' /Root 1 0 R >>'."\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function contentStream(array $lines): string
    {
        $stream = "BT\n/F1 10 Tf\n40 770 Td\n14 TL\n";

        foreach ($lines as $index => $line) {
            $escapedLine = $this->escapeText($line);

            if ($index === 0) {
                $stream .= '('.$escapedLine.") Tj\n";

                continue;
            }

            $stream .= "T*\n(".$escapedLine.") Tj\n";
        }

        return $stream.'ET';
    }

    /**
     * @return array<int, string>
     */
    private function wrapLine(string $line, int $width = 95): array
    {
        $wrapped = wordwrap($line, $width, "\n", true);

        return explode("\n", $wrapped);
    }

    private function escapeText(string $value): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\(', '\)'],
            $value,
        );
    }
}
