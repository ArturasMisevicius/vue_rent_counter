<?php

use App\Enums\LanguageStatus;
use App\Filament\Actions\Superadmin\Translations\ExportMissingTranslationsAction;
use App\Filament\Actions\Superadmin\Translations\ImportTranslationsAction;
use App\Filament\Actions\Superadmin\Translations\UpdateTranslationValueAction;
use App\Filament\Pages\TranslationManagement;
use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;
use App\Models\Language;
use App\Models\Organization;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows translation management only to superadmins and only renders active locale columns', function () {
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
        'status' => LanguageStatus::ACTIVE,
    ]);

    Language::factory()->create([
        'code' => 'lt',
        'name' => 'Lithuanian',
        'native_name' => 'Lietuviu',
        'status' => LanguageStatus::ACTIVE,
    ]);

    Language::factory()->create([
        'code' => 'qx',
        'name' => 'Inactive Test Locale',
        'native_name' => 'Inactive Test Locale',
        'status' => LanguageStatus::INACTIVE,
    ]);

    Translation::query()->create([
        'group' => 'auth',
        'key' => 'login_title',
        'values' => [
            'en' => 'Sign in',
            'lt' => 'Prisijungti',
            'qx' => 'Do not expose this',
        ],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.translation_management.title'))
        ->assertSeeText(__('superadmin.translation_management.description'))
        ->assertSeeText(__('superadmin.translation_management.actions.export_missing_csv'))
        ->assertSeeText(__('superadmin.translation_management.actions.import_csv'))
        ->assertSeeText('auth.login_title')
        ->assertSeeText('EN')
        ->assertSeeText('LT')
        ->assertSee('draftValues.auth.login_title.en', false)
        ->assertSee('draftValues.auth.login_title.lt', false)
        ->assertDontSee('draftValues.auth.login_title.qx', false)
        ->assertDontSeeText('Do not expose this');

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertForbidden();
});

it('shows the empty state when no active languages are configured', function () {
    $superadmin = User::factory()->superadmin()->create();

    Language::factory()->create([
        'code' => 'qx',
        'name' => 'Inactive Test Locale',
        'native_name' => 'Inactive Test Locale',
        'status' => LanguageStatus::INACTIVE,
    ]);

    Translation::query()->create([
        'group' => 'messages',
        'key' => 'welcome',
        'values' => [
            'qx' => 'Hidden value',
        ],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.translation_management.empty.languages'))
        ->assertDontSee('draftValues.messages.welcome.qx', false);
});

it('updates translation values inline on the page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'is_default' => true,
        'status' => LanguageStatus::ACTIVE,
    ]);

    Language::factory()->create([
        'code' => 'lt',
        'name' => 'Lithuanian',
        'native_name' => 'Lietuviu',
        'status' => LanguageStatus::ACTIVE,
    ]);

    Translation::query()->create([
        'group' => 'messages',
        'key' => 'welcome',
        'values' => [
            'en' => 'Welcome',
            'lt' => '',
        ],
    ]);

    Livewire::test(TranslationManagement::class)
        ->set('draftValues.messages.welcome.en', 'Welcome back')
        ->call('saveValue', 'messages', 'welcome', 'en');

    expect(Translation::query()->where('group', 'messages')->where('key', 'welcome')->firstOrFail()->values)
        ->toMatchArray([
            'en' => 'Welcome back',
            'lt' => '',
        ]);
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
        ->and(File::get($exportPath))->toContain('group,key,locale,value')
        ->and(File::get($exportPath))->toContain('messages,goodbye,lt');

    File::deleteDirectory($sandbox);
});
