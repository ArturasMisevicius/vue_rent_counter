<!DOCTYPE html>
<html>
<head>
    <title>Meters</title>
</head>
<body>
    <h1>Meters</h1>
    @foreach($meters as $meter)
        <p>{{ $meter->serial_number }} - {{ $meter->getServiceDisplayName() }} ({{ $meter->getUnitOfMeasurement() }})</p>
    @endforeach
</body>
</html>
