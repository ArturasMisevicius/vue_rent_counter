<!DOCTYPE html>
<html>
<head>
    <title>Properties</title>
</head>
<body>
    <h1>Properties</h1>
    @foreach($properties as $property)
        <p>{{ $property->address }} - {{ enum_label($property->type) }}</p>
    @endforeach
</body>
</html>
