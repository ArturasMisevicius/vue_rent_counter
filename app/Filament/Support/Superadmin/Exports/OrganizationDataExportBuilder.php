<?php

namespace App\Filament\Support\Superadmin\Exports;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Organization;
use ZipArchive;

class OrganizationDataExportBuilder
{
    public function build(Organization $organization): string
    {
        $path = storage_path('app/exports/organization-'.$organization->id.'-'.now()->timestamp.'.zip');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $archive->addFromString('organization.json', json_encode([
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status?->value,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $archive->addFromString('invoices.csv', $this->invoiceCsv($organization));
        $archive->addFromString('tenants.csv', $this->tenantCsv($organization));
        $archive->addFromString('meter-readings.csv', $this->meterReadingCsv($organization));

        $archive->close();

        return $path;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    protected function csv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers, ',', '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }

    protected function invoiceCsv(Organization $organization): string
    {
        return $this->csv(
            [
                __('superadmin.organizations.export_headers.invoices.invoice_number'),
                __('superadmin.organizations.export_headers.invoices.tenant_email'),
                __('superadmin.organizations.export_headers.invoices.property'),
                __('superadmin.organizations.export_headers.invoices.status'),
                __('superadmin.organizations.export_headers.invoices.currency'),
                __('superadmin.organizations.export_headers.invoices.total_amount'),
                __('superadmin.organizations.export_headers.invoices.due_date'),
                __('superadmin.organizations.export_headers.invoices.created'),
            ],
            $organization->invoices()
                ->select([
                    'id',
                    'organization_id',
                    'property_id',
                    'tenant_user_id',
                    'invoice_number',
                    'status',
                    'currency',
                    'total_amount',
                    'due_date',
                    'created_at',
                ])
                ->with([
                    'property:id,organization_id,name,unit_number',
                    'tenant:id,organization_id,email',
                ])
                ->latestBillingFirst()
                ->get()
                ->map(fn ($invoice): array => [
                    $invoice->invoice_number,
                    $invoice->tenant?->email,
                    trim(implode(' ', array_filter([$invoice->property?->displayName(), $invoice->property?->unit_number]))),
                    $invoice->status?->label() ?? $invoice->status,
                    $invoice->currency,
                    $this->formatDecimal((float) $invoice->total_amount, 2),
                    $invoice->due_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                    $invoice->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                ])
                ->all(),
        );
    }

    protected function tenantCsv(Organization $organization): string
    {
        return $this->csv(
            [
                __('superadmin.organizations.export_headers.tenants.full_name'),
                __('superadmin.organizations.export_headers.tenants.email'),
                __('superadmin.organizations.export_headers.tenants.status'),
                __('superadmin.organizations.export_headers.tenants.last_login'),
                __('superadmin.organizations.export_headers.tenants.date_created'),
            ],
            $organization->users()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'last_login_at', 'created_at'])
                ->tenants()
                ->orderedByName()
                ->get()
                ->map(fn ($tenant): array => [
                    $tenant->name,
                    $tenant->email,
                    $tenant->status?->label() ?? $tenant->status,
                    $tenant->last_login_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()),
                    $tenant->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                ])
                ->all(),
        );
    }

    protected function meterReadingCsv(Organization $organization): string
    {
        return $this->csv(
            [
                __('superadmin.organizations.export_headers.meter_readings.meter'),
                __('superadmin.organizations.export_headers.meter_readings.property'),
                __('superadmin.organizations.export_headers.meter_readings.submitted_by'),
                __('superadmin.organizations.export_headers.meter_readings.reading_value'),
                __('superadmin.organizations.export_headers.meter_readings.reading_date'),
                __('superadmin.organizations.export_headers.meter_readings.validation_status'),
                __('superadmin.organizations.export_headers.meter_readings.created'),
            ],
            $organization->meterReadings()
                ->select([
                    'id',
                    'organization_id',
                    'property_id',
                    'meter_id',
                    'submitted_by_user_id',
                    'reading_value',
                    'reading_date',
                    'validation_status',
                    'created_at',
                ])
                ->with([
                    'meter:id,organization_id,property_id,name,type',
                    'property:id,organization_id,name',
                    'submittedBy:id,name',
                ])
                ->latestFirst()
                ->get()
                ->map(fn ($reading): array => [
                    $reading->meter?->displayName(),
                    $reading->property?->displayName(),
                    $reading->submittedBy?->name,
                    $this->formatDecimal((float) $reading->reading_value, 3),
                    $reading->reading_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                    $reading->validation_status?->label() ?? $reading->validation_status,
                    $reading->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()),
                ])
                ->all(),
        );
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $precision);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
