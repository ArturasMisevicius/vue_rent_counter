<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Invoices;

use Illuminate\Support\Collection;

final class BulkInvoicePagePresenter
{
    /**
     * @param  array{valid?: array<int, array<string, mixed>>, skipped?: array<int, array<string, mixed>>}|null  $preview
     * @return array<int, array<string, mixed>>
     */
    public static function candidates(?array $preview, string $search = ''): array
    {
        $valid = collect($preview['valid'] ?? [])
            ->map(fn (array $candidate): array => [
                ...$candidate,
                'disabled' => false,
                'status_label' => null,
                'status_tone' => 'slate',
                'unit_area' => self::formatUnitArea($candidate['unit_area_sqm'] ?? null),
                'estimated_total' => self::formatMoney($candidate['total'] ?? null),
            ]);

        $skipped = collect($preview['skipped'] ?? [])
            ->map(fn (array $candidate): array => [
                ...$candidate,
                'disabled' => true,
                'status_label' => self::reasonLabel($candidate['reason'] ?? null),
                'status_tone' => ($candidate['reason'] ?? null) === 'already_billed' ? 'amber' : 'rose',
                'unit_area' => self::formatUnitArea($candidate['unit_area_sqm'] ?? null),
                'estimated_total' => null,
            ]);

        $term = mb_strtolower(trim($search));

        return $valid
            ->merge($skipped)
            ->filter(function (array $candidate) use ($term): bool {
                if ($term === '') {
                    return true;
                }

                $haystack = mb_strtolower(trim(implode(' ', array_filter([
                    (string) ($candidate['tenant_name'] ?? ''),
                    (string) ($candidate['property_name'] ?? ''),
                ]))));

                return str_contains($haystack, $term);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array{valid?: array<int, array<string, mixed>>, skipped?: array<int, array<string, mixed>>}|null  $preview
     * @param  array<int, string>  $selectedAssignments
     * @return array{
     *     selected_count: int,
     *     estimated_total: string,
     *     missing_readings: array<int, array{tenant_name: string, property_name: string}>,
     *     already_billed: array<int, array{tenant_name: string, property_name: string}>
     * }
     */
    public static function previewSummary(?array $preview, array $selectedAssignments): array
    {
        $selectedKeys = collect($selectedAssignments)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->flip();

        $selectedValid = collect($preview['valid'] ?? [])
            ->filter(fn (array $candidate): bool => $selectedKeys->has((string) ($candidate['assignment_key'] ?? '')));

        return [
            'selected_count' => $selectedValid->count(),
            'estimated_total' => self::formatMoney($selectedValid->sum(
                fn (array $candidate): float => (float) ($candidate['total'] ?? 0),
            )),
            'missing_readings' => self::warningCandidates($preview, 'ineligible_meter_readings'),
            'already_billed' => self::warningCandidates($preview, 'already_billed'),
        ];
    }

    /**
     * @param  array{created: Collection<int, object>, skipped: array<int, array<string, mixed>>}  $result
     * @param  array<int, string>  $selectedAssignments
     * @return array{
     *     created: int,
     *     failed: int,
     *     skipped: int,
     *     total: int,
     *     errors: array<int, string>,
     *     view_url: string|null
     * }
     */
    public static function generationSummary(array $result, array $selectedAssignments): array
    {
        $createdIds = $result['created']
            ->pluck('id')
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->values()
            ->all();

        return [
            'created' => $result['created']->count(),
            'failed' => max(count($selectedAssignments) - $result['created']->count(), 0),
            'skipped' => count($result['skipped']),
            'total' => count($selectedAssignments),
            'errors' => collect($result['skipped'])
                ->map(fn (array $candidate): string => self::issueMessage($candidate))
                ->values()
                ->all(),
            'view_url' => self::createdInvoicesUrl($createdIds),
        ];
    }

    /**
     * @param  array<int, int>  $invoiceIds
     */
    public static function createdInvoicesUrl(array $invoiceIds): ?string
    {
        if ($invoiceIds === []) {
            return null;
        }

        return route('filament.admin.resources.invoices.index', [
            'created_invoice_ids' => implode(',', $invoiceIds),
        ]);
    }

    private static function formatUnitArea(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $numeric = (float) $value;
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, fmod($numeric, 1.0) === 0.0 ? 0 : 2);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
        $formatted = (string) $formatter->format($numeric);

        return trim($formatted).' m²';
    }

    private static function formatMoney(mixed $value): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency((float) ($value ?? 0), 'EUR');
    }

    private static function reasonLabel(?string $reason): string
    {
        return match ($reason) {
            'already_billed' => __('admin.invoices.bulk.status.already_billed'),
            'ineligible_meter_readings' => __('admin.invoices.bulk.status.no_meter_readings'),
            default => __('admin.invoices.bulk.status.unavailable'),
        };
    }

    /**
     * @param  array{valid?: array<int, array<string, mixed>>, skipped?: array<int, array<string, mixed>>}|null  $preview
     * @return array<int, array{tenant_name: string, property_name: string}>
     */
    private static function warningCandidates(?array $preview, string $reason): array
    {
        return collect($preview['skipped'] ?? [])
            ->filter(fn (array $candidate): bool => ($candidate['reason'] ?? null) === $reason)
            ->map(fn (array $candidate): array => [
                'tenant_name' => (string) ($candidate['tenant_name'] ?? ''),
                'property_name' => (string) ($candidate['property_name'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private static function issueMessage(array $candidate): string
    {
        $tenantName = (string) ($candidate['tenant_name'] ?? __('admin.invoices.empty.tenant'));
        $propertyName = (string) ($candidate['property_name'] ?? __('admin.invoices.empty.property'));

        return sprintf(
            '%s: %s (%s)',
            self::reasonLabel($candidate['reason'] ?? null),
            $tenantName,
            $propertyName,
        );
    }
}
