<?php

namespace App\Filament\Support\Superadmin\Exports;

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
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $contents = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contents;
    }

    protected function invoiceCsv(Organization $organization): string
    {
        return $this->csv(
            ['Invoice Number', 'Tenant Email', 'Property', 'Status', 'Currency', 'Total Amount', 'Due Date', 'Created'],
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
                    trim(implode(' ', array_filter([$invoice->property?->name, $invoice->property?->unit_number]))),
                    $invoice->status?->label() ?? $invoice->status,
                    $invoice->currency,
                    number_format((float) $invoice->total_amount, 2, '.', ''),
                    $invoice->due_date?->toDateString(),
                    $invoice->created_at?->toDateString(),
                ])
                ->all(),
        );
    }

    protected function tenantCsv(Organization $organization): string
    {
        return $this->csv(
            ['Full Name', 'Email', 'Status', 'Last Login', 'Date Created'],
            $organization->users()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'last_login_at', 'created_at'])
                ->tenants()
                ->orderedByName()
                ->get()
                ->map(fn ($tenant): array => [
                    $tenant->name,
                    $tenant->email,
                    $tenant->status?->label() ?? $tenant->status,
                    $tenant->last_login_at?->toDateTimeString(),
                    $tenant->created_at?->toDateString(),
                ])
                ->all(),
        );
    }

    protected function meterReadingCsv(Organization $organization): string
    {
        return $this->csv(
            ['Meter', 'Property', 'Submitted By', 'Reading Value', 'Reading Date', 'Validation Status', 'Created'],
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
                    'meter:id,organization_id,property_id,name',
                    'property:id,organization_id,name',
                    'submittedBy:id,name',
                ])
                ->latestFirst()
                ->get()
                ->map(fn ($reading): array => [
                    $reading->meter?->name,
                    $reading->property?->name,
                    $reading->submittedBy?->name,
                    number_format((float) $reading->reading_value, 3, '.', ''),
                    $reading->reading_date?->toDateString(),
                    $reading->validation_status?->label() ?? $reading->validation_status,
                    $reading->created_at?->toDateTimeString(),
                ])
                ->all(),
        );
    }
}
