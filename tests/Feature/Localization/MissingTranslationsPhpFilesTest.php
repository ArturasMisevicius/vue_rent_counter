<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    deleteMissingTranslationsPhpFixtures();
});

afterEach(function (): void {
    deleteMissingTranslationsPhpFixtures();
});

it('treats existing php translation keys as existing entries', function (): void {
    $scanDirectory = base_path('tests/Fixtures/missing-translations-php/existing');
    $locale = 'zz_missing_translations';

    File::ensureDirectoryExists($scanDirectory);
    File::put($scanDirectory.'/sample.php', <<<'PHP'
<?php

__('demo.greeting');
PHP);

    File::ensureDirectoryExists(lang_path($locale));
    File::put(lang_path($locale.'/demo.php'), <<<'PHP'
<?php

return [
    'greeting' => 'Hello',
];
PHP);

    config()->set('laravelmissingtranslations.paths', [$scanDirectory]);
    config()->set('laravelmissingtranslations.extensions', ['php']);
    config()->set('laravelmissingtranslations.exclude_paths', []);

    artisan('missing-translations '.$locale.' --dry-run')
        ->expectsOutputToContain('Keys scanned: 1 | Existing: 1 | Missing: 0')
        ->assertExitCode(0);
});

it('writes missing keys into php translation group files', function (): void {
    $scanDirectory = base_path('tests/Fixtures/missing-translations-php/write');
    $locale = 'zy_missing_translations';

    File::ensureDirectoryExists($scanDirectory);
    File::put($scanDirectory.'/sample.php', <<<'PHP'
<?php

__('demo.farewell');
PHP);

    config()->set('laravelmissingtranslations.paths', [$scanDirectory]);
    config()->set('laravelmissingtranslations.extensions', ['php']);
    config()->set('laravelmissingtranslations.exclude_paths', []);

    artisan('missing-translations '.$locale)
        ->assertExitCode(0);

    expect(File::exists(lang_path($locale.'/demo.php')))->toBeTrue();

    $fileContents = File::get(lang_path($locale.'/demo.php'));

    $translations = require lang_path($locale.'/demo.php');

    expect($translations)->toBe([
        'farewell' => 'demo.farewell',
    ])->and($fileContents)->toContain('return [')
        ->not->toContain('return array (');
});

it('ignores malformed and sentence-like keys when writing php translation files', function (): void {
    $scanDirectory = base_path('tests/Fixtures/missing-translations-php/filter');
    $locale = 'zx_missing_translations';

    File::ensureDirectoryExists($scanDirectory);
    File::put($scanDirectory.'/sample.php', <<<'PHP'
<?php

__('admin.filters.total');
__('filament-actions::edit.single.label');
__('filament-panels::layout.actions.sidebar.expand.label');
__('landing.hero.chips');
__('admin.reports.states.');
__('shell.navigation.groups.{$groupKey}');
__('No locale specified. Provide a locale argument or use --all.');
PHP);

    config()->set('laravelmissingtranslations.paths', [$scanDirectory]);
    config()->set('laravelmissingtranslations.extensions', ['php']);
    config()->set('laravelmissingtranslations.exclude_paths', []);

    artisan('missing-translations '.$locale)
        ->assertExitCode(0);

    expect(File::exists(lang_path($locale.'/admin.php')))->toBeTrue()
        ->and(File::exists(lang_path('vendor/filament-actions/'.$locale.'/edit.php')))->toBeTrue()
        ->and(File::exists(lang_path('vendor/filament-panels/'.$locale.'/layout.php')))->toBeTrue()
        ->and(File::exists(lang_path($locale.'/filament-actions::edit.php')))->toBeFalse()
        ->and(File::exists(lang_path($locale.'/filament-panels::layout.php')))->toBeFalse()
        ->and(File::exists(lang_path($locale.'/landing.php')))->toBeFalse()
        ->and(File::exists(lang_path($locale.'/No locale specified.php')))->toBeFalse();

    $adminTranslations = require lang_path($locale.'/admin.php');
    $editTranslations = require lang_path('vendor/filament-actions/'.$locale.'/edit.php');
    $panelTranslations = require lang_path('vendor/filament-panels/'.$locale.'/layout.php');

    expect($adminTranslations)->toBe([
        'filters' => [
            'total' => 'admin.filters.total',
        ],
    ])->and($editTranslations)->toBe([
        'single' => [
            'label' => 'Edit',
        ],
    ])->and($panelTranslations)->toBe([
        'actions' => [
            'sidebar' => [
                'expand' => [
                    'label' => 'Expand sidebar',
                ],
            ],
        ],
    ]);
});

it('skips the vendor container directory when processing all php locales', function (): void {
    $scanDirectory = base_path('tests/Fixtures/missing-translations-php/all-locales');
    $locale = 'zv_missing_translations';

    File::ensureDirectoryExists($scanDirectory);
    File::put($scanDirectory.'/sample.php', <<<'PHP'
<?php

__('demo.greeting');
__('filament-actions::edit.single.label');
PHP);

    File::ensureDirectoryExists(lang_path($locale));
    File::put(lang_path($locale.'/demo.php'), <<<'PHP'
<?php

return [
    'greeting' => 'Hello',
];
PHP);

    File::ensureDirectoryExists(lang_path('vendor/filament-actions/'.$locale));
    File::put(lang_path('vendor/filament-actions/'.$locale.'/edit.php'), <<<'PHP'
<?php

return [
    'single' => [
        'label' => 'Edit',
    ],
];
PHP);

    config()->set('laravelmissingtranslations.paths', [$scanDirectory]);
    config()->set('laravelmissingtranslations.extensions', ['php']);
    config()->set('laravelmissingtranslations.exclude_paths', []);

    Artisan::call('missing-translations', [
        '--all' => true,
        '--dry-run' => true,
        '--json' => true,
    ]);

    $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    $locales = collect($output)->pluck('locale')->all();

    expect($locales)->toContain($locale)
        ->not->toContain('vendor');
});

function deleteMissingTranslationsPhpFixtures(): void
{
    File::deleteDirectory(base_path('tests/Fixtures/missing-translations-php'));
    File::deleteDirectory(lang_path('zz_missing_translations'));
    File::deleteDirectory(lang_path('zy_missing_translations'));
    File::deleteDirectory(lang_path('zx_missing_translations'));
    File::deleteDirectory(lang_path('zv_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-actions/zz_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-actions/zy_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-actions/zx_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-actions/zv_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-panels/zz_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-panels/zy_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-panels/zx_missing_translations'));
    File::deleteDirectory(lang_path('vendor/filament-panels/zv_missing_translations'));
    File::delete(lang_path('zz_missing_translations.json'));
    File::delete(lang_path('zy_missing_translations.json'));
    File::delete(lang_path('zx_missing_translations.json'));
    File::delete(lang_path('zv_missing_translations.json'));
}
