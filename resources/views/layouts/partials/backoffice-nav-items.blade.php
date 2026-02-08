@php
    $mobile = $mobile ?? false;
    $linkClass = $mobile
        ? 'block px-3 py-2 rounded-lg text-base font-semibold'
        : 'px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition';
    $activeClassName = $mobile ? $mobileActiveClass : $activeClass;
    $inactiveClassName = $mobile ? $mobileInactiveClass : $inactiveClass;

    $links = match ($userRole) {
        'superadmin' => [
            ['route' => 'superadmin.dashboard', 'prefix' => 'superadmin.dashboard', 'label' => __('app.nav.dashboard')],
            ['route' => 'superadmin.organizations.index', 'prefix' => 'superadmin.organizations', 'label' => __('app.nav.organizations')],
            ['route' => 'superadmin.buildings.index', 'prefix' => 'superadmin.buildings', 'label' => __('app.nav.buildings')],
            ['route' => 'superadmin.properties.index', 'prefix' => 'superadmin.properties', 'label' => __('app.nav.properties')],
            ['route' => 'superadmin.tenants.index', 'prefix' => 'superadmin.tenants', 'label' => __('app.nav.tenants')],
            ['route' => 'superadmin.managers.index', 'prefix' => 'superadmin.managers', 'label' => __('app.nav.managers')],
            ['route' => 'superadmin.subscriptions.index', 'prefix' => 'superadmin.subscriptions', 'label' => __('app.nav.subscriptions')],
            ['route' => 'superadmin.profile.show', 'prefix' => 'superadmin.profile', 'label' => __('app.nav.profile')],
        ],
        'admin' => [
            ['route' => 'admin.dashboard', 'prefix' => 'admin.dashboard', 'label' => __('app.nav.dashboard')],
            ['route' => 'admin.users.index', 'prefix' => 'admin.users', 'label' => __('app.nav.users')],
            ['route' => 'admin.providers.index', 'prefix' => 'admin.providers', 'label' => __('app.nav.providers')],
            ['route' => 'admin.tariffs.index', 'prefix' => 'admin.tariffs', 'label' => __('app.nav.tariffs')],
            ['route' => 'admin.settings.index', 'prefix' => 'admin.settings', 'label' => __('app.nav.settings')],
            ['route' => 'admin.audit.index', 'prefix' => 'admin.audit', 'label' => __('app.nav.audit')],
        ],
        'manager' => [
            ['route' => 'manager.dashboard', 'prefix' => 'manager.dashboard', 'label' => __('app.nav.dashboard')],
            ['route' => 'manager.properties.index', 'prefix' => 'manager.properties', 'label' => __('app.nav.properties')],
            ['route' => 'manager.buildings.index', 'prefix' => 'manager.buildings', 'label' => __('app.nav.buildings')],
            ['route' => 'manager.meters.index', 'prefix' => 'manager.meters', 'label' => __('app.nav.meters')],
            ['route' => 'manager.meter-readings.index', 'prefix' => 'manager.meter-readings', 'label' => __('app.nav.readings')],
            ['route' => 'manager.invoices.index', 'prefix' => 'manager.invoices', 'label' => __('app.nav.invoices')],
            ['route' => 'manager.reports.index', 'prefix' => 'manager.reports', 'label' => __('app.nav.reports')],
            ['route' => 'manager.profile.show', 'prefix' => 'manager.profile', 'label' => __('app.nav.profile')],
        ],
        default => [],
    };
@endphp

@foreach($links as $link)
    <a href="{{ route($link['route']) }}" class="{{ str_starts_with($currentRoute, $link['prefix']) ? $activeClassName : $inactiveClassName }} {{ $linkClass }}">
        {{ $link['label'] }}
    </a>
@endforeach
