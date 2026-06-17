<?php

declare(strict_types=1);

namespace App\Services\Architecture;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

final class ArchitectureChecklist
{
    /**
     * @return array{ready: bool, checks: list<array{label: string, status: string, summary: string, details: list<string>}>}
     */
    public function assess(): array
    {
        $checks = [
            $this->requiredFiles('Architecture docs', $this->architectureDocs()),
            $this->requiredFiles('Architecture ADRs', $this->adrs()),
            $this->requiredFiles('Module contracts', $this->moduleContracts()),
            $this->requiredFiles('Pull request template', ['.github/pull_request_template.md']),
            $this->domainDoesNotDependOnUi(),
            $this->actionsAvoidHttpGlobals(),
            $this->criticalWorkflowActionsAvoidHttpGlobals(),
            $this->criticalWorkflowTestsExist(),
            $this->filamentAvoidsDirectExternalSideEffects(),
            $this->sensitiveModelsHavePolicies(),
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['status'] === 'passed'),
            'checks' => $checks,
        ];
    }

    /**
     * @return list<string>
     */
    public function architectureDocs(): array
    {
        return [
            'docs/architecture/overview.md',
            'docs/architecture/module-boundaries.md',
            'docs/architecture/actions.md',
            'docs/architecture/policies.md',
            'docs/architecture/events-outbox.md',
            'docs/architecture/testing-scenarios.md',
            'docs/architecture/filament-guidelines.md',
            'docs/architecture/livewire-guidelines.md',
            'docs/architecture/api-guidelines.md',
            'docs/architecture/migrations.md',
            'docs/architecture/code-generation-guardrails.md',
            'docs/architecture/anti-patterns.md',
            'docs/architecture/module-contract-template.md',
            'docs/development/adding-a-feature.md',
        ];
    }

    /**
     * @return list<string>
     */
    public function adrs(): array
    {
        return [
            'docs/adr/0001-modular-monolith.md',
            'docs/adr/0002-actions-own-business-workflows.md',
            'docs/adr/0003-ui-layer-must-be-thin.md',
            'docs/adr/0004-transactional-outbox-for-side-effects.md',
            'docs/adr/0005-file-record-for-storage.md',
            'docs/adr/0006-scenario-factories-for-tests.md',
            'docs/adr/0007-feature-flags-for-risky-features.md',
            'docs/adr/0008-domain-validation-and-invariants.md',
        ];
    }

    /**
     * @return list<string>
     */
    public function moduleContracts(): array
    {
        return [
            'docs/modules/billing.md',
            'docs/modules/payments.md',
            'docs/modules/documents.md',
            'docs/modules/events-outbox.md',
            'docs/modules/kyc.md',
            'docs/modules/tenants-properties.md',
            'docs/modules/meters-readings.md',
            'docs/modules/accounting.md',
            'docs/modules/api.md',
            'docs/modules/storage.md',
            'docs/modules/support.md',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function sensitiveModelPolicies(): array
    {
        return [
            'AuditLog' => 'AuditLogPolicy',
            'BillingPeriod' => 'BillingPeriodPolicy',
            'Invoice' => 'InvoicePolicy',
            'InvoicePayment' => 'InvoicePaymentPolicy',
            'MeterReading' => 'MeterReadingPolicy',
            'RentalContract' => 'RentalContractPolicy',
            'SecurityViolation' => 'SecurityViolationPolicy',
            'TenantDocument' => 'TenantDocumentPolicy',
            'TenantKycDocument' => 'TenantKycDocumentPolicy',
            'TenantKycProfile' => 'TenantKycProfilePolicy',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function criticalWorkflowActionFiles(): array
    {
        return [
            'invoice draft creation' => 'app/Filament/Actions/Admin/Invoices/CreateInvoiceDraftAction.php',
            'invoice finalization' => 'app/Filament/Actions/Admin/Invoices/FinalizeInvoiceAction.php',
            'invoice payment recording' => 'app/Filament/Actions/Admin/Invoices/RecordInvoicePaymentAction.php',
            'invoice tenant send' => 'app/Filament/Actions/Admin/BillingReview/SendInvoiceToTenant.php',
            'payment confirmation' => 'app/Actions/Billing/ConfirmInvoicePayment.php',
            'manual payment creation' => 'app/Actions/Billing/CreateManualPayment.php',
            'meter reading correction' => 'app/Filament/Actions/Admin/BillingReview/CorrectMeterReading.php',
            'tenant invitation acceptance' => 'app/Filament/Actions/Auth/AcceptTenantInvitation.php',
            'organization impersonation start' => 'app/Filament/Actions/Superadmin/Organizations/StartOrganizationImpersonationAction.php',
            'user impersonation start' => 'app/Filament/Actions/Superadmin/Users/StartUserImpersonationAction.php',
        ];
    }

    /**
     * @return array<string, array{file: string, needles: list<string>}>
     */
    public function criticalWorkflowTestContracts(): array
    {
        return [
            'invoice send and reading correction' => [
                'file' => 'tests/Feature/Billing/BillingReviewCenterTest.php',
                'needles' => ['SendInvoiceToTenant', 'CorrectMeterReading'],
            ],
            'payment lifecycle' => [
                'file' => 'tests/Feature/Billing/PaymentTrackingAndReconciliationTest.php',
                'needles' => ['ConfirmInvoicePayment', 'CreateManualPayment', 'AuthorizationException'],
            ],
            'tenant invitation acceptance' => [
                'file' => 'tests/Feature/TenantOnboardingInvitationFlowTest.php',
                'needles' => ['AcceptTenantInvitation', 'tenant_invitation.accepted', 'tenant_portal.activated'],
            ],
            'impersonation start' => [
                'file' => 'tests/Feature/Superadmin/OrganizationImpersonationAuditTest.php',
                'needles' => ['StartOrganizationImpersonationAction', 'impersonation.started'],
            ],
        ];
    }

    /**
     * @param  list<string>  $files
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function requiredFiles(string $label, array $files): array
    {
        $missing = collect($files)
            ->reject(fn (string $file): bool => File::isFile(base_path($file)))
            ->values()
            ->all();

        return [
            'label' => $label,
            'status' => $missing === [] ? 'passed' : 'failed',
            'summary' => $missing === []
                ? sprintf('%d required file(s) exist.', count($files))
                : sprintf('%d required file(s) are missing.', count($missing)),
            'details' => $missing,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function domainDoesNotDependOnUi(): array
    {
        $domainPath = app_path('Domain');

        if (! File::isDirectory($domainPath)) {
            return [
                'label' => 'Domain boundary',
                'status' => 'passed',
                'summary' => 'No App\\Domain namespace exists in this checkout.',
                'details' => [],
            ];
        }

        $violations = $this->filesContaining($this->phpFiles([$domainPath]), [
            'App\\Filament\\',
            'Filament\\',
            'App\\Http\\Controllers\\',
            'request(',
            'auth(',
        ]);

        return [
            'label' => 'Domain boundary',
            'status' => $violations === [] ? 'passed' : 'failed',
            'summary' => $violations === []
                ? 'Domain files do not import UI/http globals.'
                : sprintf('%d domain boundary violation(s) found.', count($violations)),
            'details' => $violations,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function actionsAvoidHttpGlobals(): array
    {
        $actionsPath = app_path('Actions');

        if (! File::isDirectory($actionsPath)) {
            return [
                'label' => 'Action globals',
                'status' => 'passed',
                'summary' => 'No app/Actions directory exists.',
                'details' => [],
            ];
        }

        $violations = $this->filesContaining($this->phpFiles([$actionsPath]), [
            'request(',
            'auth(',
            'return view(',
            'response(',
            'Illuminate\\Http\\Request',
        ]);

        return [
            'label' => 'Action globals',
            'status' => $violations === [] ? 'passed' : 'failed',
            'summary' => $violations === []
                ? 'Reusable app/Actions code avoids request/auth/view/response helpers.'
                : sprintf('%d app/Actions HTTP/global violation(s) found.', count($violations)),
            'details' => $violations,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function criticalWorkflowActionsAvoidHttpGlobals(): array
    {
        $violations = collect($this->criticalWorkflowActionFiles())
            ->flatMap(function (string $file, string $workflow): array {
                $path = base_path($file);

                if (! File::isFile($path)) {
                    return ["{$workflow} is missing {$file}"];
                }

                $contents = File::get($path);

                return collect([
                    'request(',
                    'auth(',
                    'return view(',
                    'response(',
                    'Illuminate\\Http\\Request',
                    'Illuminate\\Http\\RedirectResponse',
                ])
                    ->filter(fn (string $pattern): bool => str_contains($contents, $pattern))
                    ->map(fn (string $pattern): string => "{$file} contains {$pattern}")
                    ->all();
            })
            ->values()
            ->all();

        return [
            'label' => 'Critical workflow action globals',
            'status' => $violations === [] ? 'passed' : 'failed',
            'summary' => $violations === []
                ? 'Critical workflow actions receive explicit context instead of HTTP globals.'
                : sprintf('%d critical workflow action violation(s) found.', count($violations)),
            'details' => $violations,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function criticalWorkflowTestsExist(): array
    {
        $violations = collect($this->criticalWorkflowTestContracts())
            ->flatMap(function (array $contract, string $workflow): array {
                $file = $contract['file'];
                $path = base_path($file);

                if (! File::isFile($path)) {
                    return ["{$workflow} is missing {$file}"];
                }

                $contents = File::get($path);

                return collect($contract['needles'])
                    ->reject(fn (string $needle): bool => str_contains($contents, $needle))
                    ->map(fn (string $needle): string => "{$file} does not reference {$needle}")
                    ->all();
            })
            ->values()
            ->all();

        return [
            'label' => 'Critical workflow tests',
            'status' => $violations === [] ? 'passed' : 'failed',
            'summary' => $violations === []
                ? 'Critical workflows have focused test coverage references.'
                : sprintf('%d critical workflow test gap(s) found.', count($violations)),
            'details' => $violations,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function filamentAvoidsDirectExternalSideEffects(): array
    {
        $roots = [
            app_path('Filament/Resources'),
            app_path('Filament/Pages'),
        ];

        $violations = $this->filesContaining($this->phpFiles($roots), [
            'Mail::',
            'Http::',
            'Webhook',
            'webhook(',
            'curl_',
        ]);

        return [
            'label' => 'Filament side effects',
            'status' => $violations === [] ? 'passed' : 'failed',
            'summary' => $violations === []
                ? 'Filament resources/pages do not call mail, HTTP clients, or webhooks directly.'
                : sprintf('%d direct side-effect violation(s) found.', count($violations)),
            'details' => $violations,
        ];
    }

    /**
     * @return array{label: string, status: string, summary: string, details: list<string>}
     */
    private function sensitiveModelsHavePolicies(): array
    {
        $missing = collect($this->sensitiveModelPolicies())
            ->reject(fn (string $policy, string $model): bool => File::isFile(app_path("Models/{$model}.php"))
                && File::isFile(app_path("Policies/{$policy}.php")))
            ->map(fn (string $policy, string $model): string => "{$model} requires {$policy}")
            ->values()
            ->all();

        return [
            'label' => 'Sensitive model policies',
            'status' => $missing === [] ? 'passed' : 'failed',
            'summary' => $missing === []
                ? 'Sensitive models have policy classes.'
                : sprintf('%d sensitive policy gap(s) found.', count($missing)),
            'details' => $missing,
        ];
    }

    /**
     * @param  list<string>  $roots
     * @return Collection<int, SplFileInfo>
     */
    private function phpFiles(array $roots): Collection
    {
        return collect($roots)
            ->filter(fn (string $root): bool => File::isDirectory($root))
            ->flatMap(fn (string $root): array => File::allFiles($root))
            ->filter(fn (SplFileInfo $file): bool => Str::endsWith($file->getFilename(), '.php'))
            ->values();
    }

    /**
     * @param  Collection<int, SplFileInfo>  $files
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function filesContaining(Collection $files, array $patterns): array
    {
        return $files
            ->flatMap(function (SplFileInfo $file) use ($patterns): array {
                $contents = File::get($file->getPathname());

                return collect($patterns)
                    ->filter(fn (string $pattern): bool => str_contains($contents, $pattern))
                    ->map(fn (string $pattern): string => sprintf(
                        '%s contains %s',
                        Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR),
                        $pattern,
                    ))
                    ->all();
            })
            ->values()
            ->all();
    }
}
