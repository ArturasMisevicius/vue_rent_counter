<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ __('app.nav.meters') }}</title>
</head>
<body>
    <h1>{{ __('app.nav.meters') }}</h1>
    @foreach($meters as $meter)
        <p>{{ $meter->serial_number }} - {{ $meter->getServiceDisplayName() }} ({{ $meter->getUnitOfMeasurement() }})</p>
    @endforeach
</body>
</html>
