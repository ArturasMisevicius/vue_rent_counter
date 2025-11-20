<!DOCTYPE html>
<html>
<head>
    <title>My Property</title>
</head>
<body>
    <h1>My Property</h1>
    @if($property)
        <p>Address: {{ $property->address }}</p>
        <p>Type: {{ $property->type->value }}</p>
        <p>Area: {{ $property->area_sqm }} sqm</p>
    @else
        <p>No property assigned</p>
    @endif
</body>
</html>
