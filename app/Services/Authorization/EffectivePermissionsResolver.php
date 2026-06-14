<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\User;

class EffectivePermissionsResolver
{
    public function __construct(
        private readonly ManagerPermissionService $managerPermissionService,
    ) {}

    /**
     * @return list<string>
     */
    public function for(User $user, ?Organization $organization = null): array
    {
        if (! $user->isActive()) {
            return [];
        }

        if ($user->isSuperadmin()) {
            return $this->uniquePermissions(Permission::exceptValues(...Permission::tenantOnlyCases()));
        }

        if ($user->isTenant()) {
            return $this->tenantPermissions($user, $organization);
        }

        $organization ??= $user->currentOrganization();

        if (! $organization instanceof Organization) {
            return [];
        }

        if ($user->isAdmin() || $organization->owner_user_id === $user->id) {
            return $this->adminPermissions($user, $organization);
        }

        if ($user->isManager()) {
            return $this->managerPermissions($user, $organization);
        }

        return [];
    }

    public function can(User $user, Permission|string $permission, ?Organization $organization = null): bool
    {
        $permission = $permission instanceof Permission ? $permission->value : $permission;

        return in_array($permission, $this->for($user, $organization), true);
    }

    /**
     * @return array<string, bool>
     */
    public function summary(User $user, ?Organization $organization = null): array
    {
        $permissions = $this->for($user, $organization);

        return collect(Permission::cases())
            ->mapWithKeys(fn (Permission $permission): array => [
                $permission->value => in_array($permission->value, $permissions, true),
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function adminPermissions(User $user, Organization $organization): array
    {
        if (
            ! $user->hasOrganizationRole($organization, UserRole::ADMIN)
            && $organization->owner_user_id !== $user->id
        ) {
            return [];
        }

        return $this->uniquePermissions(Permission::exceptValues(
            ...Permission::platformOnlyCases(),
            ...Permission::tenantOnlyCases(),
        ));
    }

    /**
     * @return list<string>
     */
    private function managerPermissions(User $manager, Organization $organization): array
    {
        if (! $this->managerPermissionService->isManagerForOrganization($manager, $organization)) {
            return [];
        }

        $permissions = $this->managerReadPermissions();
        $matrix = $this->managerPermissionService->getMatrix($manager, $organization);

        foreach ($matrix as $resource => $flags) {
            if ($flags['can_create'] ?? false) {
                array_push($permissions, ...$this->managerCreatePermissions($resource));
            }

            if ($flags['can_edit'] ?? false) {
                array_push($permissions, ...$this->managerEditPermissions($resource));
            }

            if ($flags['can_delete'] ?? false) {
                array_push($permissions, ...$this->managerDeletePermissions($resource));
            }
        }

        return $this->uniquePermissions($permissions);
    }

    /**
     * @return list<string>
     */
    private function tenantPermissions(User $tenant, ?Organization $organization): array
    {
        if (! $tenant->canAccessTenantPortal()) {
            return [];
        }

        if ($organization instanceof Organization && $tenant->organization_id !== $organization->id) {
            return [];
        }

        return Permission::onlyValues(...Permission::tenantOnlyCases());
    }

    /**
     * @return list<string>
     */
    private function managerReadPermissions(): array
    {
        return Permission::onlyValues(
            Permission::OrganizationDashboardView,
            Permission::BuildingsView,
            Permission::PropertiesView,
            Permission::TenantsView,
            Permission::MetersView,
            Permission::ReadingsView,
            Permission::InvoicesView,
            Permission::PaymentsView,
            Permission::ExtraChargesView,
            Permission::DocumentsView,
            Permission::ContractsView,
            Permission::ServiceConfigurationsView,
            Permission::UtilityServicesView,
            Permission::TariffsView,
            Permission::ProvidersView,
            Permission::ReportsView,
            Permission::ReportsBilling,
            Permission::NotificationsView,
            Permission::LeadsView,
        );
    }

    /**
     * @return list<string>
     */
    private function managerCreatePermissions(string $resource): array
    {
        return $this->mapResource($resource, [
            'buildings' => [Permission::BuildingsCreate],
            'properties' => [Permission::PropertiesCreate],
            'tenants' => [Permission::TenantsCreate, Permission::TenantsInvite],
            'tenant_documents' => [Permission::DocumentsUpload],
            'rental_contracts' => [Permission::ContractsCreate],
            'meters' => [Permission::MetersCreate],
            'meter_readings' => [Permission::ReadingsSubmitOnBehalf],
            'billing' => [Permission::ReadingsApprove, Permission::ReadingsReject, Permission::InvoicesGenerate],
            'extra_charges' => [Permission::ExtraChargesCreate],
            'invoices' => [Permission::InvoicesGenerate],
            'payments' => [Permission::PaymentsCreate],
            'tariffs' => [Permission::TariffsManage],
            'providers' => [Permission::ProvidersManage],
            'service_configurations' => [Permission::ServiceConfigurationsManage],
            'utility_services' => [Permission::UtilityServicesManage],
            'leads' => [Permission::LeadsCreate],
        ]);
    }

    /**
     * @return list<string>
     */
    private function managerEditPermissions(string $resource): array
    {
        return $this->mapResource($resource, [
            'buildings' => [Permission::BuildingsUpdate],
            'properties' => [Permission::PropertiesUpdate],
            'tenants' => [Permission::TenantsUpdate, Permission::TenantsInvite],
            'tenant_documents' => [Permission::DocumentsChangeVisibility],
            'rental_contracts' => [Permission::ContractsUpdate, Permission::ContractsRenew],
            'meters' => [Permission::MetersUpdate, Permission::MetersReplace],
            'meter_readings' => [Permission::ReadingsApprove, Permission::ReadingsReject, Permission::ReadingsCorrect],
            'billing' => [Permission::ReadingsApprove, Permission::ReadingsReject, Permission::ReadingsCorrect, Permission::InvoicesRecalculate],
            'extra_charges' => [Permission::ExtraChargesUpdate],
            'invoices' => [Permission::InvoicesRecalculate, Permission::InvoicesApprove, Permission::InvoicesSend],
            'payments' => [Permission::PaymentsConfirm, Permission::PaymentsReject],
            'tariffs' => [Permission::TariffsManage],
            'providers' => [Permission::ProvidersManage],
            'service_configurations' => [Permission::ServiceConfigurationsManage],
            'utility_services' => [Permission::UtilityServicesManage],
            'leads' => [Permission::LeadsUpdate],
        ]);
    }

    /**
     * @return list<string>
     */
    private function managerDeletePermissions(string $resource): array
    {
        return $this->mapResource($resource, [
            'buildings' => [Permission::BuildingsDelete],
            'properties' => [Permission::PropertiesDelete],
            'tenants' => [Permission::TenantsArchive],
            'tenant_documents' => [Permission::DocumentsArchive],
            'rental_contracts' => [Permission::ContractsTerminate],
            'meters' => [Permission::MetersArchive],
            'meter_readings' => [Permission::ReadingsVoid],
            'billing' => [Permission::InvoicesCancel],
            'extra_charges' => [Permission::ExtraChargesDelete],
            'invoices' => [Permission::InvoicesCancel, Permission::InvoicesVoid],
            'payments' => [Permission::PaymentsVoid],
            'leads' => [Permission::LeadsDelete],
        ]);
    }

    /**
     * @param  array<string, list<Permission>>  $map
     * @return list<string>
     */
    private function mapResource(string $resource, array $map): array
    {
        return array_map(
            static fn (Permission $permission): string => $permission->value,
            $map[$resource] ?? [],
        );
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function uniquePermissions(array $permissions): array
    {
        $permissions = array_values(array_unique($permissions));

        sort($permissions);

        return $permissions;
    }
}
