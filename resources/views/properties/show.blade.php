<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('properties.sections.property_details') }}</title>
</head>
<body>
    <h1>{{ __('properties.sections.property_details') }}</h1>
    <p>{{ __('properties.labels.address') }}: {{ $property->address }}</p>
    <p>{{ __('properties.labels.type') }}: {{ enum_label($property->type) }}</p>
</body>
</html>
