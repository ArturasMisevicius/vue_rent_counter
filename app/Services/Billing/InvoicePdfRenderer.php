<?php

declare(strict_types=1);

namespace App\Services\Billing;

use RuntimeException;

final class InvoicePdfRenderer
{
    public function renderMarkup(array $document): string
    {
        $lines = [
            (string) ($document['title'] ?? ''),
            (string) ($document['payment_labels']['period'] ?? ''),
        ];

        foreach ($document['summary'] ?? [] as $entry) {
            $lines[] = sprintf('%s: %s', (string) $entry['label'], (string) $entry['value']);
        }

        foreach ($document['items'] ?? [] as $item) {
            $lines[] = sprintf(
                '%s | %s | %s | %s',
                (string) $item['description'],
                (string) $item['quantity'],
                (string) $item['unit_price'],
                (string) $item['total'],
            );
        }

        foreach ($document['totals'] ?? [] as $entry) {
            $lines[] = sprintf('%s: %s', (string) $entry['label'], (string) $entry['value']);
        }

        return implode(PHP_EOL, $lines);
    }

    public function render(array $document): string
    {
        $pages = $document['pages'] ?? [];

        if ($pages === []) {
            throw new RuntimeException('Invoice PDF document has no pages to render.');
        }

        $pdf = $this->newImagick();

        foreach ($pages as $page) {
            $pageImage = $this->renderPage($document, $page, count($pages));
            $pageImage->setImageFormat('png');

            $pdf->addImage($pageImage);
        }

        $pdf->resetIterator();
        $pdf->setImageFormat('pdf');

        return $pdf->getImagesBlob();
    }

    private function fontFamily(): string
    {
        return 'Arial Unicode MS';
    }

    private function renderPage(array $document, array $page, int $pageCount): mixed
    {
        $image = $this->newImagick();
        $image->newImage(1240, 1754, $this->newImagickPixel('#f8fafc'));
        $image->setImageFormat('png');

        $this->drawRoundedPanel($image, 72, 72, 1096, 230, '#ffffff', '#dbe4f0', 42);
        $this->drawRectangle($image, 0, 0, 1240, 24, '#1d4ed8');
        $this->drawRoundedPanel($image, 820, 108, 312, 158, '#dbeafe', '#dbeafe', 30);

        $this->drawText($image, (string) ($document['subtitle'] ?? ''), 116, 138, 18, '#64748b');
        $this->drawText($image, (string) ($document['title'] ?? ''), 116, 204, 44, '#0f172a');
        $this->drawText($image, (string) ($document['payment_labels']['period'] ?? ''), 116, 250, 22, '#334155');
        $this->drawText($image, __('admin.invoices.fields.due_date').': '.(string) ($document['summary'][5]['value'] ?? '—'), 116, 284, 18, '#64748b');

        $this->drawText($image, __('admin.invoices.fields.status'), 860, 154, 18, '#64748b');
        $this->drawText($image, (string) ($document['summary'][4]['value'] ?? ''), 860, 198, 28, '#0f172a');
        $this->drawText($image, __('admin.invoices.fields.issued_date').': '.(string) ($document['issued_on'] ?? '—'), 860, 232, 18, '#64748b');
        $this->drawText($image, __('tenant.navigation.invoices').' · '.((int) $page['index'] + 1).'/'.$pageCount, 860, 264, 18, '#64748b');

        if ((bool) ($page['is_first'] ?? false)) {
            $this->drawRoundedPanel($image, 72, 336, 524, 264, '#ffffff', '#dbe4f0', 36);
            $this->drawText($image, __('admin.invoices.fields.tenant'), 110, 390, 18, '#64748b');

            $summaryLines = array_values(array_filter(array_map(
                fn (array $entry): ?string => in_array((string) $entry['label'], [
                    __('admin.invoices.fields.invoice_number'),
                    __('admin.invoices.fields.tenant'),
                    __('admin.invoices.fields.property'),
                    __('admin.invoices.fields.building'),
                ], true) ? sprintf('%s: %s', (string) $entry['label'], (string) $entry['value']) : null,
                $document['summary'] ?? [],
            )));

            foreach ($summaryLines as $index => $line) {
                $this->drawText($image, $line, 110, 438 + ($index * 42), 22, '#334155');
            }

            $this->drawRoundedPanel($image, 628, 336, 540, 264, '#ffffff', '#dbe4f0', 36);
            $this->drawText($image, (string) ($document['payment_labels']['guidance'] ?? ''), 666, 390, 18, '#64748b');
            $this->drawText($image, (string) ($document['payment_labels']['how_to_pay'] ?? ''), 666, 432, 28, '#0f172a');

            foreach ($document['payment_guidance_lines'] ?? [] as $index => $line) {
                $this->drawText($image, (string) $line, 666, 480 + ($index * 34), 22, '#334155');
            }

            foreach ($document['payment_contact_lines'] ?? [] as $index => $line) {
                $this->drawText($image, (string) $line, 666, 580 + ($index * 28), 18, '#64748b');
            }

            foreach (($document['totals'] ?? []) as $index => $card) {
                $cardX = 72 + ($index * 368);
                $this->drawRoundedPanel($image, $cardX, 636, 332, 132, '#ffffff', '#dbe4f0', 32);
                $this->drawText($image, (string) $card['label'], $cardX + 36, 688, 18, '#64748b');
                $this->drawText($image, (string) $card['value'], $cardX + 36, 734, 30, '#0f172a');
            }
        }

        $tableTop = (bool) ($page['is_first'] ?? false) ? 812 : 336;
        $tableHeight = (bool) ($page['is_first'] ?? false) ? 822 : 1298;
        $this->drawRoundedPanel($image, 72, $tableTop, 1096, $tableHeight, '#ffffff', '#dbe4f0', 40);
        $this->drawText($image, __('admin.invoices.sections.charges'), 110, $tableTop + 54, 18, '#64748b');
        $this->drawText($image, __('admin.invoices.fields.items'), 110, $tableTop + 102, 28, '#0f172a');
        $this->drawRoundedPanel($image, 104, $tableTop + 142, 1032, 58, '#dbeafe', '#dbeafe', 18);
        $this->drawText($image, (string) ($document['table_labels']['description'] ?? ''), 132, $tableTop + 180, 18, '#64748b');
        $this->drawText($image, (string) ($document['table_labels']['quantity'] ?? ''), 700, $tableTop + 180, 18, '#64748b');
        $this->drawText($image, (string) ($document['table_labels']['unit_price'] ?? ''), 870, $tableTop + 180, 18, '#64748b');
        $this->drawText($image, (string) ($document['table_labels']['total'] ?? ''), 1060, $tableTop + 180, 18, '#64748b');

        $rowY = $tableTop + 236;

        foreach ($page['items'] ?? [] as $item) {
            $descriptionLines = $item['description_lines'] ?? ['—'];
            $rowHeight = 54 + ((count($descriptionLines) - 1) * 24);
            $this->drawRoundedPanel(
                $image,
                104,
                $rowY - 28,
                1032,
                $rowHeight,
                (bool) ($item['is_adjustment'] ?? false) ? '#fff7ed' : '#ffffff',
                '#ffffff',
                16,
            );

            foreach ($descriptionLines as $index => $line) {
                $this->drawText($image, (string) $line, 132, $rowY + ($index * 24), 22, '#0f172a');
            }

            if (($item['period'] ?? '—') !== '—') {
                $this->drawText($image, (string) $item['period'], 132, $rowY + ((count($descriptionLines) - 1) * 24) + 26, 18, '#64748b');
            }

            $this->drawText($image, (string) ($item['quantity'] ?? '—'), 700, $rowY, 22, '#334155');
            $this->drawText($image, (string) ($item['unit_price'] ?? '—'), 870, $rowY, 22, '#334155');
            $this->drawText($image, (string) ($item['total'] ?? '—'), 1060, $rowY, 22, '#0f172a');

            $rowY += $rowHeight + 18;
        }

        if (($page['items'] ?? []) === []) {
            $this->drawText($image, (string) ($document['empty_items_label'] ?? ''), 132, $tableTop + 276, 22, '#64748b');
        }

        if ((bool) ($page['is_last'] ?? false)) {
            $this->drawRoundedPanel($image, 72, 1666, 1096, 52, '#ffffff', '#dbe4f0', 24);
            $this->drawText($image, (string) ($document['summary'][0]['value'] ?? ''), 110, 1699, 18, '#64748b');
            $outstandingLabel = (string) (($document['totals'][2]['label'] ?? '').': '.($document['totals'][2]['value'] ?? ''));
            $this->drawText($image, $outstandingLabel, 916, 1699, 18, '#64748b');
        }

        return $image;
    }

    private function drawRoundedPanel(mixed $image, int $x, int $y, int $width, int $height, string $fill, string $stroke, int $radius): void
    {
        $draw = $this->newImagickDraw();
        $draw->setFillColor($this->newImagickPixel($fill));
        $draw->setStrokeColor($this->newImagickPixel($stroke));
        $draw->setStrokeWidth(2);
        $draw->roundRectangle($x, $y, $x + $width, $y + $height, $radius, $radius);
        $image->drawImage($draw);
    }

    private function drawRectangle(mixed $image, int $x, int $y, int $width, int $height, string $fill): void
    {
        $draw = $this->newImagickDraw();
        $draw->setFillColor($this->newImagickPixel($fill));
        $draw->setStrokeColor($this->newImagickPixel($fill));
        $draw->rectangle($x, $y, $x + $width, $y + $height);
        $image->drawImage($draw);
    }

    private function drawText(mixed $image, string $text, int $x, int $y, int $size, string $color): void
    {
        $draw = $this->newImagickDraw();
        $draw->setFillColor($this->newImagickPixel($color));
        $draw->setFont($this->fontPath());
        $draw->setFontSize($size);
        $image->annotateImage($draw, $x, $y, 0, $text);
    }

    private function fontPath(): string
    {
        $candidates = [
            '/Library/Fonts/Arial Unicode.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('No Unicode-capable font file was found for invoice PDF rendering.');
    }

    private function newImagick(): mixed
    {
        $imagickClass = '\\Imagick';

        if (! class_exists($imagickClass)) {
            throw new RuntimeException('Imagick extension is required for invoice PDF rendering.');
        }

        return new $imagickClass;
    }

    private function newImagickDraw(): mixed
    {
        $drawClass = '\\ImagickDraw';

        if (! class_exists($drawClass)) {
            throw new RuntimeException('ImagickDraw extension class is required for invoice PDF rendering.');
        }

        return new $drawClass;
    }

    private function newImagickPixel(string $color): mixed
    {
        $pixelClass = '\\ImagickPixel';

        if (! class_exists($pixelClass)) {
            throw new RuntimeException('ImagickPixel extension class is required for invoice PDF rendering.');
        }

        return new $pixelClass($color);
    }
}
