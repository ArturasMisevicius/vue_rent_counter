<!DOCTYPE html>
<html>
<head>
    <title>Meter Details</title>
</head>
<body>
    <h1>Meter Details</h1>
    <p>Serial: {{ $meter->serial_number }}</p>
    <p>Type: {{ $meter->type->value }}</p>
</body>
</html>
