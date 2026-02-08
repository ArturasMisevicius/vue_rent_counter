<?php

declare(strict_types=1);

use App\Support\SharedTranslationKey;

it('normalizes role segments to shared', function (): void {
    expect(SharedTranslationKey::normalize('invoices.manager.index.title'))
        ->toBe('invoices.shared.index.title')
        ->and(SharedTranslationKey::normalize('tenant.profile.title'))
        ->toBe('shared.profile.title')
        ->and(SharedTranslationKey::normalize('profile.superadmin.title'))
        ->toBe('profile.shared.title')
        ->and(SharedTranslationKey::normalize('common.save'))
        ->toBe('common.save');
});

it('detects role and shared segments', function (): void {
    expect(SharedTranslationKey::hasRoleSegment('manager.profile.title'))->toBeTrue()
        ->and(SharedTranslationKey::hasRoleSegment('profile.shared.title'))->toBeFalse()
        ->and(SharedTranslationKey::hasSharedSegment('profile.shared.title'))->toBeTrue()
        ->and(SharedTranslationKey::hasSharedSegment('profile.manager.title'))->toBeFalse();
});

it('prefers role when building legacy candidates', function (): void {
    $candidates = SharedTranslationKey::legacyCandidates('shared.profile.title', 'tenant');

    expect($candidates[0])->toBe('tenant.profile.title')
        ->and($candidates)->toContain('manager.profile.title')
        ->and($candidates)->toContain('admin.profile.title')
        ->and($candidates)->toContain('superadmin.profile.title');
});
