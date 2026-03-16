<!DOCTYPE html>
<html>
<head>
    <title>{{ __('meters.headings.show', ['serial' => $meter->serial_number]) }}</title>
</head>
<body>
    <h1>{{ __('meters.headings.show', ['serial' => $meter->serial_number]) }}</h1>
    <p>{{ __('meters.labels.serial_number') }}: {{ $meter->serial_number }}</p>
    <p>{{ __('meters.labels.type') }}: {{ $meter->getServiceDisplayName() }} ({{ $meter->getUnitOfMeasurement() }})</p>
</body>
</html>
