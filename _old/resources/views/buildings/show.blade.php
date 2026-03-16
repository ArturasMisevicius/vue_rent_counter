<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('buildings.pages.show.title') }}</title>
</head>
<body>
    <h1>{{ __('buildings.pages.show.heading') }}</h1>
    <p>{{ __('buildings.labels.name') }}: {{ $building->display_name }}</p>
    <p>{{ __('buildings.labels.address') }}: {{ $building->address }}</p>
    <p>{{ __('buildings.labels.total_apartments') }}: {{ $building->total_apartments }}</p>
</body>
</html>
