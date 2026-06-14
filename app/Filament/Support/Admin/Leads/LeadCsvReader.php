<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Leads;

use SplFileObject;

class LeadCsvReader
{
    /**
     * @return array{headers: list<string>, rows: list<array<string, string|null>>}
     */
    public function read(string $path): array
    {
        $delimiter = $this->detectDelimiter($path);
        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($delimiter);

        $headers = [];
        $rows = [];

        foreach ($file as $index => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $values = array_map(fn (mixed $value): ?string => $this->cleanCell($value), $row);

            if ($index === 0) {
                $headers = array_values(array_filter(
                    array_map(fn (?string $header): string => $this->cleanHeader((string) $header), $values),
                    fn (string $header): bool => $header !== '',
                ));

                continue;
            }

            if ($headers === []) {
                continue;
            }

            $rowValues = [];

            foreach ($headers as $headerIndex => $header) {
                $rowValues[$header] = $values[$headerIndex] ?? null;
            }

            $rows[] = $rowValues;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ',';
        }

        $line = fgets($handle) ?: '';
        fclose($handle);

        $candidates = [',', ';', "\t"];
        $counts = collect($candidates)
            ->mapWithKeys(fn (string $candidate): array => [$candidate => substr_count($line, $candidate)])
            ->all();

        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function cleanHeader(string $value): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value);
    }

    private function cleanCell(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim((string) $value);

        return $cleaned === '' ? null : $cleaned;
    }
}
