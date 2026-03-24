<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('prints the phase 1 branch-protection payload and verification commands', function () {
    config()->set('services.github.token', null);

    $exitCode = Artisan::call('ops:phase1-guardrails-branch-protection');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)
        ->toContain('Phase 1 Guardrails Branch Protection')
        ->toContain('ArturasMisevicius/vue_rent_counter')
        ->toContain('Phase 1 Guardrails')
        ->toContain('Payload')
        ->toContain('"strict": true')
        ->toContain('"context": "Phase 1 Guardrails"')
        ->toContain('Apply command')
        ->toContain('PATCH')
        ->toContain('Authorization: Bearer ${GITHUB_TOKEN')
        ->toContain('Verify command')
        ->toContain("rg 'Phase 1 Guardrails'")
        ->toContain('Result: WAITING FOR GITHUB_TOKEN');
});

it('documents the phase 1 branch-protection helper workflow', function () {
    $path = base_path('docs/operations/phase-1-guardrails-branch-protection.md');

    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))
        ->toContain('php artisan ops:phase1-guardrails-branch-protection')
        ->toContain('ArturasMisevicius/vue_rent_counter')
        ->toContain('Phase 1 Guardrails')
        ->toContain('GITHUB_TOKEN')
        ->toContain('/branches/main/protection/required_status_checks');
});
