<?php

declare(strict_types=1);

namespace App\Filament\Support\Help;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Route;

final class SetupChecklistBuilder
{
    /**
     * @return array<int, array{key: string, label: string, description: string, action: string, url: string, complete: bool, status: string}>
     */
    public function forUser(User $user): array
    {
        $organization = $user->currentOrganization();

        if ($organization === null) {
            return [];
        }

        $organizationId = (int) $organization->id;

        return [
            $this->item('building', $this->hasBuilding($organizationId), 'filament.admin.resources.buildings.index'),
            $this->item('property', $this->hasProperty($organizationId), 'filament.admin.resources.properties.index'),
            $this->item('tenant', $this->hasTenant($organizationId), 'filament.admin.resources.tenants.index'),
            $this->item('assignment', $this->hasAssignment($organizationId), 'filament.admin.resources.tenants.index'),
            $this->item('meters', $this->hasMeter($organizationId), 'filament.admin.resources.meters.index'),
            $this->item('services', $this->hasServiceConfiguration($organizationId), 'filament.admin.resources.service-configurations.index'),
            $this->item('invitation', $this->hasInvitation($organizationId), 'filament.admin.resources.tenants.index'),
            $this->item('invoice', $this->hasInvoice($organizationId), 'filament.admin.resources.invoices.index'),
        ];
    }

    private function hasBuilding(int $organizationId): bool
    {
        return Building::query()
            ->where('organization_id', $organizationId)
            ->exists();
    }

    private function hasProperty(int $organizationId): bool
    {
        return Property::query()
            ->where('organization_id', $organizationId)
            ->exists();
    }

    private function hasTenant(int $organizationId): bool
    {
        return User::query()
            ->forOrganization($organizationId)
            ->tenants()
            ->exists();
    }

    private function hasAssignment(int $organizationId): bool
    {
        return PropertyAssignment::query()
            ->where('organization_id', $organizationId)
            ->exists();
    }

    private function hasMeter(int $organizationId): bool
    {
        return Meter::query()
            ->forOrganization($organizationId)
            ->exists();
    }

    private function hasServiceConfiguration(int $organizationId): bool
    {
        return ServiceConfiguration::query()
            ->forOrganization($organizationId)
            ->active()
            ->exists();
    }

    private function hasInvitation(int $organizationId): bool
    {
        return OrganizationInvitation::query()
            ->forOrganization($organizationId)
            ->whereNotNull('sent_at')
            ->exists();
    }

    private function hasInvoice(int $organizationId): bool
    {
        return Invoice::query()
            ->forOrganization($organizationId)
            ->exists();
    }

    /**
     * @return array{key: string, label: string, description: string, action: string, url: string, complete: bool, status: string}
     */
    private function item(string $key, bool $complete, string $routeName): array
    {
        return [
            'key' => $key,
            'label' => __("help.checklist.items.{$key}.label"),
            'description' => __("help.checklist.items.{$key}.description"),
            'action' => __("help.checklist.items.{$key}.action"),
            'url' => Route::has($routeName) ? route($routeName) : '#',
            'complete' => $complete,
            'status' => $complete ? __('help.checklist.status.complete') : __('help.checklist.status.pending'),
        ];
    }
}
