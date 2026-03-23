<?php

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\FrameworkShowcases\FrameworkShowcaseResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\PlatformNotifications\PlatformNotificationResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Providers\ProviderResource;
use App\Filament\Resources\SecurityViolations\SecurityViolationResource;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\UtilityServices\UtilityServiceResource;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('keeps every Filament resource mapped to regression coverage and its expected pages', function () {
    $coverageMatrix = [
        AuditLogResource::class => [
            'pages' => ['index'],
            'test' => 'tests/Feature/Superadmin/AuditLogsResourceTest.php',
        ],
        BuildingResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/BuildingsResourceTest.php',
        ],
        FrameworkShowcaseResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Filament/FrameworkStudioTest.php',
        ],
        InvoiceResource::class => [
            'pages' => ['index', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/InvoicesResourceTest.php',
        ],
        LanguageResource::class => [
            'pages' => ['index', 'create', 'edit'],
            'test' => 'tests/Feature/Superadmin/LanguagesResourceTest.php',
        ],
        MeterReadingResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/MeterReadingsResourceTest.php',
        ],
        MeterResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/MetersResourceTest.php',
        ],
        OrganizationResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Superadmin/OrganizationsResourceTest.php',
        ],
        PlatformNotificationResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Superadmin/PlatformNotificationsResourceTest.php',
        ],
        PropertyResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/PropertiesResourceTest.php',
        ],
        ProviderResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/ProvidersResourceTest.php',
        ],
        SecurityViolationResource::class => [
            'pages' => ['index'],
            'test' => 'tests/Feature/Superadmin/SecurityViolationsResourceTest.php',
        ],
        ServiceConfigurationResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/TariffsAndProvidersTest.php',
        ],
        SubscriptionResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Superadmin/SubscriptionsResourceTest.php',
        ],
        TariffResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/TariffsResourceTest.php',
        ],
        TenantResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/TenantsResourceTest.php',
        ],
        UtilityServiceResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Admin/TariffsAndProvidersTest.php',
        ],
        UserResource::class => [
            'pages' => ['index', 'create', 'view', 'edit'],
            'test' => 'tests/Feature/Superadmin/UsersResourceTest.php',
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
