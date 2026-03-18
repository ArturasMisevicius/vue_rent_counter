<?php

use App\Filament\Actions\Superadmin\Translations\ExportMissingTranslationsAction;
use App\Filament\Actions\Superadmin\Translations\ImportTranslationsAction;
use App\Filament\Actions\Superadmin\Translations\UpdateTranslationValueAction;
use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;
use App\Models\Language;
use App\Models\Organization;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('shows translation management only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'is_default' => true,
    ]);

    Translation::query()->create([
        'group' => 'auth',
        'key' => 'login_title',
        'values' => [
            'en' => 'Sign in',
        ],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertSuccessful()
        ->assertSeeText('Translation Management')
        ->assertSeeText('auth.login_title');

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertForbidden();
});

it('updates imports and exports translation files inside a filesystem sandbox', function () {
    $sandbox = base_path('tests/tmp/translations-'.Str::uuid());

    File::ensureDirectoryExists($sandbox.'/en');
    File::ensureDirectoryExists($sandbox.'/lt');

    File::put($sandbox.'/en/messages.php', <<<'PHP'
<?php

return [
    'welcome' => 'Welcome',
    'goodbye' => 'Goodbye',
];
PHP);

    File::put($sandbox.'/lt/messages.php', <<<'PHP'
<?php

return [
    'welcome' => '',
];
PHP);

    $service = new TranslationCatalogService($sandbox);

    app(UpdateTranslationValueAction::class)->handle($service, 'messages', 'welcome', 'lt', 'Sveiki');

    $exportPath = app(ExportMissingTranslationsAction::class)->handle($service, 'lt');

    File::put($sandbox.'/import.csv', implode("\n", [
        'group,key,locale,value',
        'messages,goodbye,lt,Viso gero',
    ]));

    app(ImportTranslationsAction::class)->handle($service, $sandbox.'/import.csv');

    $lithuanianTranslations = require $sandbox.'/lt/messages.php';

    expect($lithuanianTranslations['welcome'])->toBe('Sveiki')
        ->and($lithuanianTranslations['goodbye'])->toBe('Viso gero')
        ->and(File::exists($exportPath))->toBeTrue()
        ->and(File::get($exportPath))->toContain('messages,goodbye,lt');

    File::deleteDirectory($sandbox);
});
