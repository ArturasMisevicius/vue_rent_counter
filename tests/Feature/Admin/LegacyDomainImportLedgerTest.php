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
