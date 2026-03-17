<?php

use App\Enums\LanguageStatus;
use App\Models\Building;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use App\Models\Translation;
use App\Support\Geography\BalticReferenceCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates baltic country and city reference tables and models', function () {
    expect(Schema::hasTable('countries'))->toBeTrue()
        ->and(Schema::hasTable('cities'))->toBeTrue()
        ->and(class_exists(Country::class))->toBeTrue()
        ->and(class_exists(City::class))->toBeTrue();
});

it('seeds baltic-only locales together with multilingual country and city translations', function () {
    Language::factory()->create([
        'code' => 'es',
        'name' => 'Spanish',
        'native_name' => 'Español',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => false,
    ]);

    $this->seed(DatabaseSeeder::class);

    $activeLanguages = Language::query()
        ->where('status', LanguageStatus::ACTIVE)
        ->pluck('code')
        ->all();

    expect(array_keys(config('app.supported_locales')))->toBe(['en', 'lt', 'ru', 'es'])
        ->and(array_keys(config('tenanto.locales')))->toBe(['en', 'lt', 'ru', 'es'])
        ->and($activeLanguages)->toEqualCanonicalizing(['en', 'lt', 'ru'])
        ->and(Language::query()->where('code', 'es')->first()?->status)->toBe(LanguageStatus::INACTIVE)
        ->and(Country::query()->count())->toBe(3)
        ->and(City::query()->count())->toBe(count(BalticReferenceCatalog::cities()))
        ->and(Country::query()->baltic()->with('cities')->get()->pluck('code')->all())->toEqualCanonicalizing(['EE', 'LT', 'LV'])
        ->and(Translation::query()->where('group', 'countries')->count())->toBe(3)
        ->and(Translation::query()->where('group', 'cities')->count())->toBe(count(BalticReferenceCatalog::cities()))
        ->and(Translation::query()->where('group', 'countries')->where('key', 'LT')->firstOrFail()->values)->toBe([
            'en' => 'Lithuania',
            'lt' => 'Lietuva',
            'ru' => 'Литва',
        ])
        ->and(Translation::query()->where('group', 'cities')->where('key', 'tallinn')->firstOrFail()->values)->toBe([
            'en' => 'Tallinn',
            'lt' => 'Talinas',
            'ru' => 'Таллин',
        ]);
});

it('keeps generated building addresses inside the baltic geography catalog', function () {
    $cityMap = collect(BalticReferenceCatalog::cities())
        ->mapWithKeys(fn (array $city): array => [$city['name'] => $city['country_code']]);

    $buildings = Building::factory()->count(20)->make();

    foreach ($buildings as $building) {
        expect($cityMap->has($building->city))->toBeTrue()
            ->and($cityMap->get($building->city))->toBe($building->country_code)
            ->and((string) $building->postal_code)->not->toBe('');
    }
});
