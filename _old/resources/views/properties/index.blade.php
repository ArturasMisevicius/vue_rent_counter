<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('app.nav.properties') }}</title>
</head>
<body>
    <h1>{{ __('app.nav.properties') }}</h1>
    @foreach($properties as $property)
        <p>{{ $property->address }} - {{ enum_label($property->type) }}</p>
    @endforeach
</body>
</html>
