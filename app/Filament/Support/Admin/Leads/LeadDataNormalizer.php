<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Leads;

class LeadDataNormalizer
{
    public function phone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $normalized = preg_replace('/[^0-9+]/', '', (string) $phone);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '00')) {
            $normalized = '+'.substr($normalized, 2);
        }

        return $normalized;
    }

    public function email(?string $email): ?string
    {
        if (! filled($email)) {
            return null;
        }

        return strtolower(trim((string) $email));
    }

    public function decimal(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string) $value);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        if ($normalized === null || $normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    public function integer(?string $value): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $normalized = preg_replace('/[^0-9]/', '', (string) $value);

        return filled($normalized) ? (int) $normalized : null;
    }

    public function currency(?string $value): string
    {
        if (! filled($value)) {
            return 'EUR';
        }

        return strtoupper(substr(trim((string) $value), 0, 3));
    }
}
