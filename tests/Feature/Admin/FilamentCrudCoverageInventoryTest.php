<?php

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('keeps every Filament resource mapped to regression coverage and its expected pages', function () {
    $coverageMatrix = [
        BuildingResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/BuildingsResourceTest.php',
        ],
        OrganizationResource::class => [
            'pages' => ['index', 'view'],
            'test' => 'tests/Feature/Superadmin/OrganizationsResourceTest.php',
        ],
        PropertyResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/PropertiesResourceTest.php',
        ],
        TenantResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/TenantsResourceTest.php',
        ],
    ];

    $discoveredResources = collect(File::allFiles(app_path('Filament/Resources')))
        ->filter(fn (SplFileInfo $file) => Str::endsWith($file->getFilename(), 'Resource.php'))
        ->map(function (SplFileInfo $file): string {
            $relativePath = Str::of($file->getRelativePathname())
                ->replace('/', '\\')
                ->replace('.php', '');

            return 'App\\Filament\\Resources\\'.$relativePath;
        })
        ->sort()
        ->values()
        ->all();

    expect(array_keys($coverageMatrix))
        ->toEqualCanonicalizing($discoveredResources);

    foreach ($coverageMatrix as $resourceClass => $expectations) {
        expect(array_keys($resourceClass::getPages()))
            ->toEqualCanonicalizing($expectations['pages']);

        expect(File::exists(base_path($expectations['test'])))
            ->toBeTrue("Expected regression coverage file for [{$resourceClass}] at [{$expectations['test']}].");
    }
});
