<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case PlatformDashboardView = 'platform.dashboard.view';
    case OrganizationsView = 'organizations.view';
    case OrganizationsManage = 'organizations.manage';
    case SubscriptionsManage = 'subscriptions.manage';
    case LanguagesManage = 'languages.manage';
    case PlatformAuditView = 'platform.audit.view';
    case SystemSettingsManage = 'system.settings.manage';
    case ImpersonationStart = 'impersonation.start';

    case OrganizationDashboardView = 'organization.dashboard.view';

    case BuildingsView = 'buildings.view';
    case BuildingsCreate = 'buildings.create';
    case BuildingsUpdate = 'buildings.update';
    case BuildingsDelete = 'buildings.delete';

    case PropertiesView = 'properties.view';
    case PropertiesCreate = 'properties.create';
    case PropertiesUpdate = 'properties.update';
    case PropertiesDelete = 'properties.delete';

    case TenantsView = 'tenants.view';
    case TenantsCreate = 'tenants.create';
    case TenantsUpdate = 'tenants.update';
    case TenantsArchive = 'tenants.archive';
    case TenantsInvite = 'tenants.invite';

    case MetersView = 'meters.view';
    case MetersCreate = 'meters.create';
    case MetersUpdate = 'meters.update';
    case MetersReplace = 'meters.replace';
    case MetersArchive = 'meters.archive';

    case ReadingsView = 'readings.view';
    case ReadingsSubmitOnBehalf = 'readings.submit_on_behalf';
    case ReadingsApprove = 'readings.approve';
    case ReadingsReject = 'readings.reject';
    case ReadingsCorrect = 'readings.correct';
    case ReadingsVoid = 'readings.void';

    case InvoicesView = 'invoices.view';
    case InvoicesGenerate = 'invoices.generate';
    case InvoicesRecalculate = 'invoices.recalculate';
    case InvoicesApprove = 'invoices.approve';
    case InvoicesSend = 'invoices.send';
    case InvoicesCancel = 'invoices.cancel';
    case InvoicesVoid = 'invoices.void';

    case PaymentsView = 'payments.view';
    case PaymentsCreate = 'payments.create';
    case PaymentsConfirm = 'payments.confirm';
    case PaymentsReject = 'payments.reject';
    case PaymentsVoid = 'payments.void';
    case PaymentsUploadProof = 'payments.upload_proof';

    case ExtraChargesView = 'extra_charges.view';
    case ExtraChargesCreate = 'extra_charges.create';
    case ExtraChargesUpdate = 'extra_charges.update';
    case ExtraChargesDelete = 'extra_charges.delete';

    case DocumentsView = 'documents.view';
    case DocumentsUpload = 'documents.upload';
    case DocumentsChangeVisibility = 'documents.change_visibility';
    case DocumentsDownload = 'documents.download';
    case DocumentsArchive = 'documents.archive';
    case DocumentsDelete = 'documents.delete';

    case ContractsView = 'contracts.view';
    case ContractsCreate = 'contracts.create';
    case ContractsUpdate = 'contracts.update';
    case ContractsTerminate = 'contracts.terminate';
    case ContractsRenew = 'contracts.renew';

    case ServiceConfigurationsView = 'service_configurations.view';
    case ServiceConfigurationsManage = 'service_configurations.manage';
    case UtilityServicesView = 'utility_services.view';
    case UtilityServicesManage = 'utility_services.manage';
    case TariffsView = 'tariffs.view';
    case TariffsManage = 'tariffs.manage';
    case ProvidersView = 'providers.view';
    case ProvidersManage = 'providers.manage';

    case ReportsView = 'reports.view';
    case ReportsBilling = 'reports.billing';
    case NotificationsView = 'notifications.view';
    case RemindersSend = 'reminders.send';

    case TeamView = 'team.view';
    case TeamManage = 'team.manage';

    case SettingsBilling = 'settings.billing';
    case SettingsOrganization = 'settings.organization';
    case SettingsSubscription = 'settings.subscription';

    case AuditView = 'audit.view';
    case AuditExport = 'audit.export';

    case LeadsView = 'leads.view';
    case LeadsCreate = 'leads.create';
    case LeadsUpdate = 'leads.update';
    case LeadsDelete = 'leads.delete';

    case TenantPortalAccess = 'tenant_portal.access';
    case TenantPropertyViewOwn = 'tenant_portal.property.view_own';
    case TenantInvoicesViewOwn = 'tenant_portal.invoices.view_own';
    case TenantInvoicesDownloadOwn = 'tenant_portal.invoices.download_own';
    case TenantReadingsSubmitOwn = 'tenant_portal.readings.submit_own';
    case TenantDocumentsViewOwnVisible = 'tenant_portal.documents.view_own_visible';
    case TenantDocumentsDownloadOwnVisible = 'tenant_portal.documents.download_own_visible';
    case TenantProfileUpdateOwn = 'tenant_portal.profile.update_own';
    case TenantKycManageOwn = 'tenant_portal.kyc.manage_own';
    case TenantPaymentProofUploadOwn = 'tenant_portal.payment_proof.upload_own';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function onlyValues(self ...$permissions): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            $permissions,
        );
    }

    /**
     * @return list<string>
     */
    public static function exceptValues(self ...$excluded): array
    {
        return array_values(array_filter(
            self::values(),
            static fn (string $permission): bool => ! in_array($permission, self::onlyValues(...$excluded), true),
        ));
    }

    /**
     * @return list<string>
     */
    public static function sensitiveValues(): array
    {
        return self::onlyValues(...self::sensitiveCases());
    }

    /**
     * @return list<self>
     */
    public static function platformOnlyCases(): array
    {
        return [
            self::PlatformDashboardView,
            self::OrganizationsView,
            self::OrganizationsManage,
            self::SubscriptionsManage,
            self::LanguagesManage,
            self::PlatformAuditView,
            self::SystemSettingsManage,
            self::ImpersonationStart,
        ];
    }

    /**
     * @return list<self>
     */
    public static function tenantOnlyCases(): array
    {
        return [
            self::TenantPortalAccess,
            self::TenantPropertyViewOwn,
            self::TenantInvoicesViewOwn,
            self::TenantInvoicesDownloadOwn,
            self::TenantReadingsSubmitOwn,
            self::TenantDocumentsViewOwnVisible,
            self::TenantDocumentsDownloadOwnVisible,
            self::TenantProfileUpdateOwn,
            self::TenantKycManageOwn,
            self::TenantPaymentProofUploadOwn,
        ];
    }

    /**
     * @return list<self>
     */
    public static function sensitiveCases(): array
    {
        return [
            self::InvoicesVoid,
            self::PaymentsVoid,
            self::DocumentsDelete,
            self::AuditExport,
            self::SettingsBilling,
            self::SettingsSubscription,
            self::TeamManage,
            self::TariffsManage,
            self::ImpersonationStart,
        ];
    }

    public function isSensitive(): bool
    {
        return in_array($this, self::sensitiveCases(), true);
    }

    public function group(): string
    {
        return explode('.', $this->value, 2)[0];
    }
}
