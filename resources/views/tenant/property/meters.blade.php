<!DOCTYPE html>
<html>
<head>
    <title>{{ __('tenant.property.meters_title') }}</title>
</head>
<body>
    <h1>{{ __('tenant.property.meters_title') }}</h1>
    @if($meters->count() > 0)
        @foreach($meters as $meter)
            <p>{{ enum_label($meter->type) }} - {{ $meter->serial_number }}</p>
        @endforeach
    @else
        <p>{{ __('tenant.property.no_meters') }}</p>
    @endif
</body>
</html>
