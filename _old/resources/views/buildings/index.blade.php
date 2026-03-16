<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('app.nav.buildings') }}</title>
</head>
<body>
    <h1>{{ __('buildings.labels.buildings') }}</h1>
    @foreach($buildings as $building)
        <p>
            {{ $building->display_name }} ({{ $building->address }}) — {{ __('buildings.labels.total_apartments') }}: {{ $building->total_apartments }}
        </p>
    @endforeach
</body>
</html>
