<?php

use App\Enums\LanguageStatus;
use App\Filament\Pages\TranslationManagement;
use App\Models\Language;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createTranslationSandbox(): string
{
    $path = storage_path('framework/testing/lang-'.Str::uuid()->toString());

    File::deleteDirectory($path);
    File::ensureDirectoryExists($path);

    return $path;
}

function writeTranslationGroup(string $root, string $locale, string $group, array $translations): void
{
    File::ensureDirectoryExists("{$root}/{$locale}");

    File::put(
        "{$root}/{$locale}/{$group}.php",
        "<?php\n\nreturn ".var_export($translations, true).";\n",
    );
}

function readTranslationGroup(string $root, string $locale, string $group): array
{
    /** @var array<string, mixed> $translations */
    $translations = require "{$root}/{$locale}/{$group}.php";

    return $translations;
}

it('manages translation files through inline updates plus missing export and import flows', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => true,
    ]);
    Language::factory()->create([
        'code' => 'lt',
        'name' => 'Lithuanian',
        'native_name' => 'Lietuviu',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => false,
    ]);

    $langPath = createTranslationSandbox();

    writeTranslationGroup($langPath, 'en', 'shell', [
        'nav' => [
            'profile' => 'My Profile',
            'logout' => 'Log Out',
        ],
        'search' => 'Search everything',
    ]);
    writeTranslationGroup($langPath, 'lt', 'shell', [
        'nav' => [
            'profile' => 'Mano profilis',
        ],
    ]);

    config()->set('tenanto.localization.translation_sources.php_array_files', $langPath);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.translation-management'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    $component = Livewire::test(TranslationManagement::class)
        ->set('selectedLocale', 'lt')
        ->set('selectedGroup', 'shell')
        ->call('loadCatalog')
        ->assertSee('nav.profile')
        ->assertSee('nav.logout')
        ->assertSee('search');

    $component->call('updateTranslationValue', 'nav.logout', 'Atsijungti');

    expect(data_get(readTranslationGroup($langPath, 'lt', 'shell'), 'nav.logout'))
        ->toBe('Atsijungti');

    $component->call('exportMissingTranslations');

    $exportPath = $component->instance()->exportedMissingTranslationsPath;

    expect($exportPath)->toBeFile();

    /** @var array<string, mixed> $exportedTranslations */
    $exportedTranslations = require $exportPath;

    expect($exportedTranslations)->toBe([
        'search' => 'Search everything',
    ]);

    $component->call('importTranslations', $exportPath);

    expect(readTranslationGroup($langPath, 'lt', 'shell'))->toMatchArray([
        'nav' => [
            'profile' => 'Mano profilis',
            'logout' => 'Atsijungti',
        ],
        'search' => 'Search everything',
    ]);

    File::deleteDirectory($langPath);
});
