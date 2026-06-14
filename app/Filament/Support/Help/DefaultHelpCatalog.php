<?php

declare(strict_types=1);

namespace App\Filament\Support\Help;

use App\Enums\HelpArticleCategory;
use App\Enums\HelpAudienceRole;
use App\Models\HelpArticle;
use App\Models\HelpContext;
use App\Models\User;
use Illuminate\Support\Collection;

final class DefaultHelpCatalog
{
    /**
     * @return Collection<int, HelpArticle>
     */
    public function articlesFor(User $user, ?string $category = null, ?string $search = null): Collection
    {
        $visibleRoles = HelpAudienceRole::visibleValuesForUser($user);

        return collect($this->articleRows())
            ->filter(fn (array $row): bool => in_array($row['role'], $visibleRoles, true))
            ->filter(fn (array $row): bool => blank($category) || $row['category'] === $category)
            ->filter(fn (array $row): bool => $this->matchesSearch($row, $search))
            ->map(fn (array $row): HelpArticle => new HelpArticle($row))
            ->values();
    }

    /**
     * @return Collection<int, HelpContext>
     */
    public function contextsFor(User $user, string $pageKey): Collection
    {
        $visibleRoles = HelpAudienceRole::visibleValuesForUser($user);

        return collect($this->contextRows())
            ->filter(fn (array $row): bool => $row['page_key'] === $pageKey)
            ->filter(fn (array $row): bool => in_array($row['role'], $visibleRoles, true))
            ->sortBy([
                ['sort_order', 'asc'],
                ['article_slug', 'asc'],
            ])
            ->map(fn (array $row): HelpContext => new HelpContext($row))
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articleRows(): array
    {
        return [
            [
                'slug' => 'getting-started',
                'category' => HelpArticleCategory::GETTING_STARTED->value,
                'title' => 'Getting Started',
                'body' => "What this feature does\nGuides a new organization through the first clean setup path.\n\nWhen to use it\nUse this before generating the first invoice.\n\nStep-by-step flow\n1. Create organization settings.\n2. Add a building.\n3. Add a property.\n4. Add a tenant.\n5. Assign the tenant to the property.\n6. Add meters.\n7. Configure services and tariffs.\n8. Send the tenant invitation.\n9. Generate the first billing period.\n10. Review readings.\n11. Approve and send the invoice.\n\nCommon mistakes\nGenerating invoices before tenants, meters, services, and tariffs are connected usually creates blocked invoices.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['setup', 'organization', 'first invoice'],
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'billing-flow',
                'category' => HelpArticleCategory::BILLING->value,
                'title' => 'Billing Flow',
                'body' => "What this feature does\nExplains how billing periods, readings, review, invoices, and payments move together.\n\nStep-by-step flow\nBilling period created -> draft invoices generated -> tenant submits readings -> admin reviews readings -> invoice calculation preview -> invoice approved -> invoice sent -> payment received.\n\nImportant statuses\ndraft: invoice is editable.\nwaiting_for_readings: required tenant readings are missing.\nreadings_submitted: tenant has sent values for review.\nready_for_review: calculation can be checked.\napproved: invoice is accepted for sending.\nsent: tenant has received it.\npaid: payment is complete.\noverdue: due date passed with open balance.\ncancelled: invoice should not be collected.\n\nCommon mistakes\nApproving before reading review or missing tariff checks creates confusing totals.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['billing period', 'invoice status', 'approval'],
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'meter-readings',
                'category' => HelpArticleCategory::READINGS->value,
                'title' => 'Meter Readings',
                'body' => "What this feature does\nMeter readings turn previous and current values into billable consumption.\n\nImportant fields\nPrevious reading is the last approved value from the earlier billing period.\nCurrent reading is the number shown on the meter now.\nConsumption is current reading minus previous reading.\n\nExample\nPrevious reading: 1200\nCurrent reading: 1350\nConsumption: 150 kWh\nTariff: 0.25 EUR\nTotal: 37.50 EUR\n\nTroubleshooting\nIf current is lower than previous, check whether the meter was replaced, reset, or entered incorrectly. If a tenant made a mistake, reject or void the incorrect reading and request a corrected value.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['readings', 'consumption', 'previous reading', 'current reading'],
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'slug' => 'service-configuration',
                'category' => HelpArticleCategory::SERVICES->value,
                'title' => 'Service Configuration',
                'body' => "What this feature does\nDefines how services are calculated and added to invoices.\n\nWhen to use it\nConfigure services before invoice generation.\n\nRequired setup\nProvider, tariff, assignment, tenant visibility, and either a meter rule or a fixed amount.\n\nExamples\nElectricity: meter-based, unit kWh, tariff EUR/kWh, tenant reading required.\nInternet: fixed monthly, amount 20 EUR, no meter needed.\nRepair: one-time charge, clear tenant-visible description, optional attachment.\n\nCommon mistakes\nMissing tariff, no assignment, empty tenant-visible description, meter-based service without meters, or fixed service without an amount.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['services', 'tariffs', 'fixed monthly', 'meter based', 'extra charges'],
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'slug' => 'invoice-calculation',
                'category' => HelpArticleCategory::INVOICES->value,
                'title' => 'Invoice Calculation',
                'body' => "What this feature does\nExplains how invoice items are assembled from approved readings, fixed services, extra charges, discounts, and penalties.\n\nStep-by-step flow\n1. Approved readings become consumption invoice items.\n2. Fixed services are added for the billing period.\n3. Extra charges are included once, unless they are recurring and not already included for that period.\n4. Discounts reduce the total.\n5. Penalties increase the total.\n\nExample\nElectricity: 150 kWh x 0.25 EUR = 37.50 EUR\nInternet: monthly fee = 20.00 EUR\nRepair: manual charge = 50.00 EUR\nTotal: 107.50 EUR\n\nImportant rule\nSent or paid invoices should preserve their historical calculation even if a tariff changes later.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['invoice items', 'discount', 'penalty', 'extra charges'],
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'slug' => 'tenant-onboarding',
                'category' => HelpArticleCategory::TENANTS->value,
                'title' => 'Tenant Onboarding',
                'body' => "What this feature does\nExplains how a tenant becomes connected to a property and gains portal access.\n\nStep-by-step flow\n1. Create tenant.\n2. Assign tenant to a property.\n3. Send invitation.\n4. Tenant opens the link.\n5. Tenant creates a password.\n6. Tenant portal becomes active.\n\nTroubleshooting\nIf the link expired, resend the invitation from the tenant profile. If portal access must stop, disable portal access without deleting the tenant history.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['tenant', 'invitation', 'portal access'],
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'slug' => 'rental-contracts',
                'category' => HelpArticleCategory::CONTRACTS->value,
                'title' => 'Rental Contracts',
                'body' => "What this feature does\nKeeps rental contract documents connected to tenants, property assignments, and tenant visibility rules.\n\nRequired setup\nTenant, active property assignment, contract dates, status, and uploaded file when available.\n\nStep-by-step flow\nAttach the contract to the tenant assignment, choose whether it is tenant-visible, configure expiry reminders, then renew or terminate when the agreement changes.\n\nCommon mistakes\nUploading a contract without an active assignment or making an internal-only document visible to the tenant.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['contracts', 'documents', 'expiry reminders'],
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'slug' => 'documents',
                'category' => HelpArticleCategory::DOCUMENTS->value,
                'title' => 'Documents',
                'body' => "What this feature does\nStores tenant documents, contract files, receipts, and supporting attachments with visibility rules.\n\nImportant fields\nTenant-visible documents can be downloaded by the tenant. Internal notes and internal-only attachments are for admins and managers only.\n\nCommon mistakes\nUploading a receipt or contract with the wrong visibility or forgetting to replace expired documents.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['documents', 'attachments', 'tenant visible'],
                'is_active' => true,
                'sort_order' => 80,
            ],
            [
                'slug' => 'troubleshooting',
                'category' => HelpArticleCategory::TROUBLESHOOTING->value,
                'title' => 'Troubleshooting',
                'body' => "What this feature does\nLists common blockers and the next place to fix them.\n\nInvoice was not generated\nCheck tenant assignment, billing period, service configuration, and subscription limits.\n\nTenant cannot submit readings\nCheck portal access, active assignment, required meter service, and reading window.\n\nReading is lower than previous value\nCheck for meter replacement, tenant error, or reset. Reject the bad reading and request a corrected value.\n\nTariff is missing\nOpen Billing -> Tariffs and attach the tariff to the service configuration.\n\nInvoice cannot be approved\nOpen Billing Review Center and fix missing readings, duplicate items, or configuration errors.\n\nTenant did not receive invitation\nResend invitation from the tenant profile and confirm the email address.",
                'locale' => 'en',
                'role' => HelpAudienceRole::ADMIN->value,
                'tags' => ['troubleshooting', 'missing tariff', 'duplicate readings', 'invitation'],
                'is_active' => true,
                'sort_order' => 90,
            ],
            [
                'slug' => 'tenant-submit-readings',
                'category' => HelpArticleCategory::READINGS->value,
                'title' => 'How to Submit Readings',
                'body' => "Enter the current number shown on your meter. Do not enter consumption manually; the system calculates consumption from the previous approved reading and your current reading.\n\nIf you notice a mistake after submitting, contact your property manager so they can reject the incorrect reading and request a corrected value.",
                'locale' => 'en',
                'role' => HelpAudienceRole::TENANT->value,
                'tags' => ['tenant readings', 'submit readings'],
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'tenant-view-invoices',
                'category' => HelpArticleCategory::INVOICES->value,
                'title' => 'How to View Invoices',
                'body' => 'Your invoice total is calculated from approved meter readings, fixed services, and additional charges. Open the invoice page to review the final amount, due date, line items, and downloadable document.',
                'locale' => 'en',
                'role' => HelpAudienceRole::TENANT->value,
                'tags' => ['tenant invoices', 'invoice total'],
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'tenant-download-documents',
                'category' => HelpArticleCategory::DOCUMENTS->value,
                'title' => 'How to Download Documents',
                'body' => 'Tenant-visible documents can be opened from the Documents page. Internal admin notes and internal-only attachments are not shown in the tenant portal.',
                'locale' => 'en',
                'role' => HelpAudienceRole::TENANT->value,
                'tags' => ['tenant documents', 'download'],
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'slug' => 'tenant-update-profile',
                'category' => HelpArticleCategory::TENANTS->value,
                'title' => 'How to Update Your Profile',
                'body' => 'Use the profile page to update your contact details, preferred language, password, and profile image. Contact your manager if a locked tenancy detail is incorrect.',
                'locale' => 'en',
                'role' => HelpAudienceRole::TENANT->value,
                'tags' => ['tenant profile', 'language', 'password'],
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'slug' => 'tenant-rejected-reading',
                'category' => HelpArticleCategory::READINGS->value,
                'title' => 'What to Do if a Reading Was Rejected',
                'body' => 'A reading can be rejected when it is lower than the previous approved value, duplicated, or unclear. Check the meter again and submit the corrected current number when your manager asks for a new reading.',
                'locale' => 'en',
                'role' => HelpAudienceRole::TENANT->value,
                'tags' => ['rejected reading', 'tenant readings'],
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'slug' => 'tenant-overdue-payment',
                'category' => HelpArticleCategory::BILLING->value,
                'title' => 'What to Do if Payment Is Overdue',
                'body' => 'Open the invoice to confirm the due date, total, and payment instructions. If you already paid, contact your manager with the payment reference or upload proof when the portal asks for it.',
                'locale' => 'en',
                'role' => HelpAudienceRole::TENANT->value,
                'tags' => ['overdue payment', 'payment proof'],
                'is_active' => true,
                'sort_order' => 60,
            ],
        ];
    }

    /**
     * @return array<int, array{page_key: string, article_slug: string, role: string, sort_order: int}>
     */
    private function contextRows(): array
    {
        return [
            ['page_key' => 'service_configurations.index', 'article_slug' => 'service-configuration', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'service_configurations.create', 'article_slug' => 'service-configuration', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'service_configurations.edit', 'article_slug' => 'service-configuration', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'tariffs.index', 'article_slug' => 'service-configuration', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'meters.index', 'article_slug' => 'meter-readings', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'meter_readings.index', 'article_slug' => 'meter-readings', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'invoices.review', 'article_slug' => 'billing-flow', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'invoices.review', 'article_slug' => 'invoice-calculation', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 20],
            ['page_key' => 'invoices.index', 'article_slug' => 'invoice-calculation', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'tenant.create', 'article_slug' => 'tenant-onboarding', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'tenant.invitation', 'article_slug' => 'tenant-onboarding', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'tenant.profile', 'article_slug' => 'tenant-onboarding', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'rental_contracts.create', 'article_slug' => 'rental-contracts', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'documents.index', 'article_slug' => 'documents', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'extra_charges.index', 'article_slug' => 'invoice-calculation', 'role' => HelpAudienceRole::ADMIN->value, 'sort_order' => 10],
            ['page_key' => 'tenant.readings', 'article_slug' => 'tenant-submit-readings', 'role' => HelpAudienceRole::TENANT->value, 'sort_order' => 10],
            ['page_key' => 'tenant.invoices', 'article_slug' => 'tenant-view-invoices', 'role' => HelpAudienceRole::TENANT->value, 'sort_order' => 10],
            ['page_key' => 'tenant.documents', 'article_slug' => 'tenant-download-documents', 'role' => HelpAudienceRole::TENANT->value, 'sort_order' => 10],
            ['page_key' => 'tenant.profile', 'article_slug' => 'tenant-update-profile', 'role' => HelpAudienceRole::TENANT->value, 'sort_order' => 10],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function matchesSearch(array $row, ?string $search): bool
    {
        $term = mb_strtolower(trim((string) $search));

        if ($term === '') {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', [
            $row['title'],
            $row['body'],
            $row['category'],
            implode(' ', $row['tags'] ?? []),
        ]));

        return str_contains($haystack, $term);
    }
}
