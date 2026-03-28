<x-filament-panels::page>
    @include('livewire.pages.reports.reports-page', [
        'report' => $this->report,
        'rows' => $this->rows,
        'statusOptions' => $this->statusOptions(),
        'organizationOptions' => $this->organizationOptions,
        'canSelectOrganization' => $this->canSelectOrganization(),
        'hasOrganizationContext' => $this->hasOrganizationContext(),
    ])
</x-filament-panels::page>
