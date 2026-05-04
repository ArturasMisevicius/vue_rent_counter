<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('keeps role templates from rendering legacy hardcoded English labels', function (): void {
    $files = [
        resource_path('views/filament/resources/projects/overview.blade.php') => [
            'Project identity',
            'No metadata recorded.',
            'Schedule health',
            'Budget health',
            'Projected share per tenant',
        ],
        resource_path('views/filament/resources/projects/audit-log-modal.blade.php') => [
            'No audit entries recorded for this project.',
        ],
        resource_path('views/filament/resources/users/dossier.blade.php') => [
            'User Dossier',
            'Total Sections',
            'Full superadmin support view',
        ],
        resource_path('views/filament/resources/users/partials/dossier-tree.blade.php') => [
            'No values recorded.',
            'Record {{ $index + 1 }}',
        ],
        resource_path('views/filament/tables/columns/project-progress-bar.blade.php') => [
            'Progress',
        ],
        resource_path('views/welcome.blade.php') => [
            'Operations workspace',
            '<span>Workflow</span>',
            '<span>Status</span>',
            '<span>Owner</span>',
        ],
    ];

    foreach ($files as $file => $legacyStrings) {
        $contents = File::get($file);

        foreach ($legacyStrings as $legacyString) {
            expect($contents)->not->toContain($legacyString);
        }
    }
});

it('resolves template translation keys for every supported locale', function (string $locale): void {
    app()->setLocale($locale);

    $keys = [
        'admin.projects.sections.project_identity',
        'admin.projects.overview.no_audit_entries',
        'admin.projects.columns.progress',
        'superadmin.users.dossier.title',
        'superadmin.users.dossier.no_values_recorded',
        'landing.preview.operations_workspace',
        'landing.preview.columns.workflow',
        'shell.navigation.groups.operations',
    ];

    foreach ($keys as $key) {
        expect(__($key))->not->toBe($key);
    }
})->with(['en', 'lt', 'es', 'ru']);
