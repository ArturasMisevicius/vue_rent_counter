<!DOCTYPE html>
<html>
<head>
    <title>Property Details</title>
</head>
<body>
    <h1>Property Details</h1>
    <p>Address: {{ $property->address }}</p>
    <p>Type: {{ enum_label($property->type) }}</p>
</body>
</html>
