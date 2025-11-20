<!DOCTYPE html>
<html>
<head>
    <title>Meter Details</title>
</head>
<body>
    <h1>Meter Details</h1>
    <p>Type: {{ $meter->type->value }}</p>
    <p>Serial: {{ $meter->serial_number }}</p>
</body>
</html>
