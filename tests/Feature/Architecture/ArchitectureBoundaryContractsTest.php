<?php

declare(strict_types=1);

use App\Services\Architecture\ArchitectureChecklist;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

it('passes the architecture checklist command', function (): void {
    $this->artisan('architecture:check')
        ->expectsOutputToContain('Architecture Check')
        ->expectsOutputToContain('Architecture docs')
        ->expectsOutputToContain('Architecture ADRs')
        ->expectsOutputToContain('Module contracts')
        ->expectsOutputToContain('Critical workflow action globals')
        ->expectsOutputToContain('Critical workflow tests')
        ->expectsOutputToContain('Sensitive model policies')
        ->expectsOutputToContain('Result: PASSED')
        ->assertExitCode(0);
});

it('keeps required architecture docs, ADRs, module contracts, and the PR template in place', function (): void {
    $checklist = app(ArchitectureChecklist::class);
    $requiredFiles = [
        ...$checklist->architectureDocs(),
        ...$checklist->adrs(),
        ...$checklist->moduleContracts(),
        '.github/pull_request_template.md',
    ];

    foreach ($requiredFiles as $requiredFile) {
        expect(File::isFile(base_path($requiredFile)))->toBeTrue("Missing {$requiredFile}.");
    }
});

it('keeps domain and reusable app actions away from UI and HTTP globals', function (): void {
    $violations = [];

    if (File::isDirectory(app_path('Domain'))) {
        $violations = [
            ...$violations,
            ...architectureFilesContaining(
                [app_path('Domain')],
                [
                    'App\\Filament\\',
                    'Filament\\',
                    'App\\Http\\Controllers\\',
                    'request(',
                    'auth(',
                ],
            ),
        ];
    }

    $violations = [
        ...$violations,
        ...architectureFilesContaining(
            [app_path('Actions')],
            [
                'request(',
                'auth(',
                'return view(',
                'response(',
                'Illuminate\\Http\\Request',
            ],
        ),
    ];

    expect($violations)->toBeEmpty();
});

it('keeps filament resources and pages from direct mail or webhook calls', function (): void {
    $violations = architectureFilesContaining(
        [
            app_path('Filament/Resources'),
            app_path('Filament/Pages'),
        ],
        [
            'Mail::',
            'Http::',
            'Webhook',
            'webhook(',
            'curl_',
        ],
    );

    expect($violations)->toBeEmpty();
});

it('requires policies for sensitive models', function (): void {
    foreach (app(ArchitectureChecklist::class)->sensitiveModelPolicies() as $model => $policy) {
        expect(File::isFile(app_path("Models/{$model}.php")))->toBeTrue("Missing sensitive model {$model}.")
            ->and(File::isFile(app_path("Policies/{$policy}.php")))->toBeTrue("Missing policy {$policy}.");
    }
});

it('keeps selected billing workflows wired through named action paths', function (): void {
    expect(File::get(app_path('Filament/Resources/Payments/Tables/PaymentsTable.php')))
        ->toContain('ConfirmInvoicePayment')
        ->and(File::get(app_path('Actions/Billing/CreateManualPayment.php')))
        ->toContain('ConfirmInvoicePayment')
        ->and(File::get(app_path('Filament/Actions/Admin/Invoices/RecordInvoicePaymentAction.php')))
        ->toContain('CreateManualPayment')
        ->and(File::get(app_path('Filament/Resources/Invoices/Tables/InvoicesTable.php')))
        ->toContain('SendInvoiceToTenant')
        ->and(File::get(app_path('Filament/Pages/BillingReviewCenter.php')))
        ->toContain('CorrectMeterReading')
        ->and(File::get(app_path('Filament/Pages/BillingInvoiceReview.php')))
        ->toContain('CorrectMeterReading');
});

it('keeps critical workflow actions away from request and auth helpers', function (): void {
    $violations = collect(app(ArchitectureChecklist::class)->criticalWorkflowActionFiles())
        ->flatMap(fn (string $file): array => architectureFilesContaining(
            [base_path(dirname($file))],
            [
                'request(',
                'auth(',
                'return view(',
                'response(',
                'Illuminate\\Http\\Request',
                'Illuminate\\Http\\RedirectResponse',
            ],
        ))
        ->filter(fn (string $violation): bool => collect(app(ArchitectureChecklist::class)->criticalWorkflowActionFiles())
            ->contains(fn (string $file): bool => str_starts_with($violation, $file)))
        ->values()
        ->all();

    expect($violations)->toBeEmpty();
});

it('keeps critical workflow test contracts in place', function (): void {
    foreach (app(ArchitectureChecklist::class)->criticalWorkflowTestContracts() as $workflow => $contract) {
        expect(File::isFile(base_path($contract['file'])))->toBeTrue("Missing {$workflow} test file.");

        $contents = File::get(base_path($contract['file']));

        foreach ($contract['needles'] as $needle) {
            expect(str_contains($contents, $needle))->toBeTrue("{$workflow} tests should reference {$needle}.");
        }
    }
});

/**
 * @param  list<string>  $roots
 * @param  list<string>  $patterns
 * @return list<string>
 */
function architectureFilesContaining(array $roots, array $patterns): array
{
    return collect($roots)
        ->filter(fn (string $root): bool => File::isDirectory($root))
        ->flatMap(fn (string $root): array => File::allFiles($root))
        ->filter(fn (SplFileInfo $file): bool => Str::endsWith($file->getFilename(), '.php'))
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
