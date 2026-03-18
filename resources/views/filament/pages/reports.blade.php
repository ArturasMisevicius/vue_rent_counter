<x-filament-panels::page>
    @include('livewire.pages.reports.reports-page', [
        'report' => $this->report,
        'rows' => $this->rows,
        'statusOptions' => $this->statusOptions(),
        'hasOrganizationContext' => $this->organizationId !== null,
    ])
</x-filament-panels::page>
