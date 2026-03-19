<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tracks every top-level old model in the legacy import ledger', function () {
    $ledgerPath = base_path('docs/superpowers/legacy-domain-import-ledger.md');

    expect(file_exists($ledgerPath))->toBeTrue();

    $ledger = file_get_contents($ledgerPath);

    expect($ledger)
        ->not->toBeFalse()
        ->toContain('Activity')
        ->toContain('Currency')
        ->toContain('InvoiceItem')
        ->toContain('Translation')
        ->toContain('Task')
        ->toContain('merge')
        ->toContain('import')
        ->toContain('defer');
});

it('does not leave import or merge ledger rows pointing at missing current models', function () {
    $ledgerPath = base_path('docs/superpowers/legacy-domain-import-ledger.md');

    expect(file_exists($ledgerPath))->toBeTrue();

    $ledger = file_get_contents($ledgerPath);

    expect($ledger)->not->toBeFalse();

    preg_match_all(
        '/^\| [^|]+ \| (import|merge) \| `App\\\\Models\\\\([^`]+)` \|/m',
        $ledger,
        $matches,
        PREG_SET_ORDER,
    );

    $missingModels = collect($matches)
        ->map(fn (array $match): string => $match[2])
        ->filter(fn (string $model): bool => ! file_exists(app_path("Models/{$model}.php")))
        ->values()
        ->all();

    expect($missingModels)->toBeEmpty();
});
